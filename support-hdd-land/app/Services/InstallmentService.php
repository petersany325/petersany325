<?php

namespace App\Services;

use App\Models\InstallmentCheck;
use App\Models\InstallmentItem;
use App\Models\InstallmentPayment;
use App\Models\InstallmentPlan;
use App\Models\Reception;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InstallmentService
{
    public function __construct(
        private AccountingService $accounting,
        private CustomerDebtService $debt,
        private ReceptionSettlementService $settlement,
        private InstallmentReminderService $reminders,
    ) {}

    /**
     * @param  array<string,mixed>  $data
     * @param  list<array{due_date:string,amount:int,notes?:string}>|null  $manualItems
     */
    public function createPlan(array $data, ?array $manualItems = null): InstallmentPlan
    {
        return DB::transaction(function () use ($data, $manualItems) {
            $mode = ($data['schedule_mode'] ?? 'auto') === 'manual' ? 'manual' : 'auto';
            $plan = InstallmentPlan::create([
                'customer_id' => $data['customer_id'],
                'reception_id' => $data['reception_id'] ?? null,
                'created_by' => Auth::id(),
                'title' => $data['title'] ?? null,
                'total_amount' => (int) ($data['total_amount'] ?? 0),
                'down_payment' => (int) ($data['down_payment'] ?? 0),
                'installment_count' => (int) ($data['installment_count'] ?? 0),
                'interval_days' => max(1, (int) ($data['interval_days'] ?? 30)),
                'schedule_mode' => $mode,
                'start_date' => $data['start_date'] ?? null,
                'status' => 'active',
                'notes' => $data['notes'] ?? null,
                'guarantor_name' => $data['guarantor_name'] ?? null,
                'guarantor_phone' => $data['guarantor_phone'] ?? null,
                'guarantor_national_code' => $data['guarantor_national_code'] ?? null,
                'guarantor_address' => $data['guarantor_address'] ?? null,
                'guarantor_notes' => $data['guarantor_notes'] ?? null,
            ]);

            if ($mode === 'manual') {
                $this->buildManualSchedule($plan, $manualItems ?? []);
            } else {
                $this->buildAutoSchedule($plan);
            }

            $plan->load('items');
            $plan->forceFill([
                'installment_count' => $plan->items->count(),
                'total_amount' => (int) $plan->items->sum('amount'),
            ])->save();

            return $plan->fresh(['items', 'customer', 'reception']);
        });
    }

    public function buildAutoSchedule(InstallmentPlan $plan): void
    {
        $plan->items()->delete();

        $total = max(0, (int) $plan->total_amount);
        $down = max(0, min((int) $plan->down_payment, $total));
        $count = max(1, (int) $plan->installment_count);
        $interval = max(1, (int) $plan->interval_days);
        $start = $plan->start_date
            ? Carbon::parse($plan->start_date)->startOfDay()
            : now()->startOfDay();

        $seq = 1;
        if ($down > 0) {
            InstallmentItem::create([
                'installment_plan_id' => $plan->id,
                'sequence' => $seq++,
                'due_date' => $start->toDateString(),
                'amount' => $down,
                'paid_amount' => 0,
                'status' => 'pending',
                'notes' => 'پیش‌پرداخت',
            ]);
        }

        $rest = $total - $down;
        if ($rest <= 0 && $down <= 0) {
            throw ValidationException::withMessages([
                'total_amount' => 'مبلغ کل اقساط باید بزرگ‌تر از صفر باشد.',
            ]);
        }

        if ($rest > 0) {
            $base = intdiv($rest, $count);
            $remainder = $rest % $count;
            for ($i = 0; $i < $count; $i++) {
                $amount = $base + ($i < $remainder ? 1 : 0);
                $due = $start->copy()->addDays($interval * ($i + ($down > 0 ? 1 : 0)));
                // If no down payment, first installment on start_date
                if ($down <= 0) {
                    $due = $start->copy()->addDays($interval * $i);
                }
                InstallmentItem::create([
                    'installment_plan_id' => $plan->id,
                    'sequence' => $seq++,
                    'due_date' => $due->toDateString(),
                    'amount' => $amount,
                    'paid_amount' => 0,
                    'status' => 'pending',
                ]);
            }
        }
    }

    /**
     * @param  list<array{due_date:string,amount:int,notes?:string}>  $items
     */
    public function buildManualSchedule(InstallmentPlan $plan, array $items): void
    {
        $plan->items()->delete();
        if ($items === []) {
            throw ValidationException::withMessages([
                'items' => 'حداقل یک قسط با تاریخ و مبلغ وارد کنید.',
            ]);
        }

        $seq = 1;
        foreach ($items as $row) {
            $amount = (int) ($row['amount'] ?? 0);
            $due = (string) ($row['due_date'] ?? '');
            if ($amount < 1 || $due === '') {
                continue;
            }
            InstallmentItem::create([
                'installment_plan_id' => $plan->id,
                'sequence' => $seq++,
                'due_date' => $due,
                'amount' => $amount,
                'paid_amount' => 0,
                'status' => 'pending',
                'notes' => $row['notes'] ?? null,
            ]);
        }

        if ($seq === 1) {
            throw ValidationException::withMessages([
                'items' => 'هیچ قسط معتبری ثبت نشد.',
            ]);
        }
    }

    /**
     * @param  array{method:string,amount:int,note?:string,reception_id?:int,check?:array}  $data
     * @return array{ok:bool,message:string,payment?:InstallmentPayment}
     */
    public function collect(InstallmentItem $item, array $data): array
    {
        $item->loadMissing(['plan.customer', 'plan.reception']);
        $plan = $item->plan;
        if (! $plan || $plan->status === 'cancelled') {
            return ['ok' => false, 'message' => 'طرح اقساط فعال نیست.'];
        }
        if ($item->status === 'cancelled') {
            return ['ok' => false, 'message' => 'این قسط لغو شده است.'];
        }

        $remaining = $item->remainingAmount();
        if ($remaining <= 0) {
            return ['ok' => false, 'message' => 'این قسط قبلاً تسویه شده است.'];
        }

        $method = (string) ($data['method'] ?? 'cash');
        if (! array_key_exists($method, InstallmentPayment::METHODS)) {
            throw ValidationException::withMessages(['method' => 'روش دریافت نامعتبر است.']);
        }

        $amount = (int) ($data['amount'] ?? 0);
        if ($amount < 1) {
            throw ValidationException::withMessages(['amount' => 'مبلغ را وارد کنید.']);
        }
        if ($amount > $remaining) {
            throw ValidationException::withMessages([
                'amount' => 'مبلغ نمی‌تواند از مانده قسط ('.number_format($remaining).' تومان) بیشتر باشد.',
            ]);
        }

        $note = trim((string) ($data['note'] ?? ''));

        return DB::transaction(function () use ($item, $plan, $method, $amount, $note, $data) {
            $item = InstallmentItem::query()->lockForUpdate()->findOrFail($item->id);
            $freshRemain = $item->remainingAmount();
            if ($amount > $freshRemain) {
                throw ValidationException::withMessages([
                    'amount' => 'مبلغ نمی‌تواند از مانده قسط بیشتر باشد.',
                ]);
            }

            $check = null;
            if ($method === 'check') {
                $check = $this->storeCheck($plan, $item, $data['check'] ?? [], $amount);
            }

            $paymentId = null;
            if ($method !== 'check') {
                $paymentId = $this->postAgainstReceivable($plan, $method, $amount, $note !== '' ? $note : 'دریافت قسط #'.$item->sequence);
            }

            $item->forceFill([
                'paid_amount' => (int) $item->paid_amount + $amount,
            ])->save();
            $item->refreshStatus();

            $ip = InstallmentPayment::create([
                'installment_plan_id' => $plan->id,
                'installment_item_id' => $item->id,
                'payment_id' => $paymentId,
                'installment_check_id' => $check?->id,
                'received_by' => Auth::id(),
                'amount' => $amount,
                'method' => $method,
                'paid_at' => now(),
                'note' => $note !== '' ? $note : null,
                'thanks_sms_sent' => false,
            ]);

            if (! empty($data['item_notes'])) {
                $item->forceFill(['notes' => (string) $data['item_notes']])->save();
            }

            $plan->refreshStatus();

            $this->reminders->sendThanks($ip->fresh(['item', 'plan.customer']));

            return [
                'ok' => true,
                'message' => 'پرداخت قسط ثبت شد'.($amount < $freshRemain ? ' (ناقص).' : '.'),
                'payment' => $ip,
            ];
        });
    }

    /**
     * @param  array<string,mixed>  $checkData
     */
    public function storeCheck(InstallmentPlan $plan, ?InstallmentItem $item, array $checkData, int $fallbackAmount = 0): InstallmentCheck
    {
        $amount = (int) ($checkData['amount'] ?? $fallbackAmount);
        if ($amount < 1) {
            throw ValidationException::withMessages(['check.amount' => 'مبلغ چک را وارد کنید.']);
        }

        return InstallmentCheck::create([
            'installment_plan_id' => $plan->id,
            'installment_item_id' => $item?->id,
            'check_number' => $checkData['check_number'] ?? null,
            'bank_name' => $checkData['bank_name'] ?? null,
            'branch' => $checkData['branch'] ?? null,
            'account_no' => $checkData['account_no'] ?? null,
            'amount' => $amount,
            'due_date' => $checkData['due_date'] ?? null,
            'holder_name' => $checkData['holder_name'] ?? null,
            'status' => $checkData['status'] ?? 'held',
            'is_custody' => array_key_exists('is_custody', $checkData) ? (bool) $checkData['is_custody'] : true,
            'notes' => $checkData['notes'] ?? null,
        ]);
    }

    private function postAgainstReceivable(InstallmentPlan $plan, string $method, int $amount, string $note): ?int
    {
        $reception = null;
        if ($plan->reception_id) {
            $reception = Reception::query()->find($plan->reception_id);
        }
        if (! $reception || $reception->remainingAmount() <= 0) {
            $open = $this->debt->openTickets($plan->customer, 20);
            $reception = $open->first(fn (Reception $r) => $r->remainingAmount() > 0);
        }

        if ($reception && $reception->remainingAmount() > 0) {
            $payAmount = min($amount, $reception->remainingAmount());
            $result = $this->settlement->collectReceivable($reception, [
                'method' => $method,
                'amount' => $payAmount,
                'note' => $note,
            ]);
            if (! ($result['ok'] ?? false)) {
                throw ValidationException::withMessages(['amount' => $result['message'] ?? 'ثبت دریافت ناموفق بود.']);
            }

            // If installment amount exceeds this ticket remaining, allocate rest to next tickets / manual.
            $left = $amount - $payAmount;
            if ($left > 0) {
                $this->allocateRest($plan, $method, $left, $note);
            }

            return isset($result['payment_id']) ? (int) $result['payment_id'] : null;
        }

        // No open ticket: post manual AR reduction
        $cashCode = $this->accounting->methodAccountCode($method);
        $this->accounting->createManual(
            $note.' — مشتری #'.$plan->customer_id,
            [
                [$cashCode, $amount, 0, 'دریافت قسط'],
                [AccountingService::RECEIVABLE, 0, $amount, 'کاهش دریافتنی اقساط'],
            ],
            now()->toDateString(),
            (int) $plan->customer_id
        );

        return null;
    }

    private function allocateRest(InstallmentPlan $plan, string $method, int $amount, string $note): void
    {
        $left = $amount;
        $tickets = $this->debt->openTickets($plan->customer, 50);
        foreach ($tickets as $ticket) {
            if ($left <= 0) {
                break;
            }
            if ($plan->reception_id && (int) $ticket->id === (int) $plan->reception_id) {
                continue;
            }
            $remain = $ticket->remainingAmount();
            if ($remain <= 0) {
                continue;
            }
            $chunk = min($left, $remain);
            $this->settlement->collectReceivable($ticket, [
                'method' => $method,
                'amount' => $chunk,
                'note' => $note,
            ]);
            $left -= $chunk;
        }

        if ($left > 0) {
            $cashCode = $this->accounting->methodAccountCode($method);
            $this->accounting->createManual(
                $note.' (مازاد) — مشتری #'.$plan->customer_id,
                [
                    [$cashCode, $left, 0, 'دریافت قسط'],
                    [AccountingService::RECEIVABLE, 0, $left, 'کاهش دریافتنی اقساط'],
                ],
                now()->toDateString(),
                (int) $plan->customer_id
            );
        }
    }

    public function refreshOverdueStatuses(): int
    {
        $n = 0;
        InstallmentItem::query()
            ->whereIn('status', ['pending', 'partial', 'overdue'])
            ->where('paid_amount', '<', DB::raw('amount'))
            ->whereDate('due_date', '<', now()->toDateString())
            ->orderBy('id')
            ->chunkById(100, function ($items) use (&$n) {
                foreach ($items as $item) {
                    $before = $item->status;
                    $item->refreshStatus();
                    if ($item->status !== $before) {
                        $n++;
                    }
                }
            });

        return $n;
    }

    public function cancelPlan(InstallmentPlan $plan, ?string $reason = null): void
    {
        DB::transaction(function () use ($plan, $reason) {
            $plan->forceFill([
                'status' => 'cancelled',
                'notes' => trim(($plan->notes ? $plan->notes."\n" : '').($reason ? 'لغو: '.$reason : 'لغو طرح')),
            ])->save();
            $plan->items()->where('status', '!=', 'paid')->update(['status' => 'cancelled']);
        });
    }
}
