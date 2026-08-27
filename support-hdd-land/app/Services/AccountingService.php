<?php

namespace App\Services;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\Part;
use App\Models\Payment;
use App\Models\Reception;
use App\Models\ReceptionPart;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AccountingService
{
    public const CASH = '1110';
    public const CARD = '1120';
    public const TRANSFER = '1130';
    public const RECEIVABLE = '1210';
    public const INVENTORY = '1310';
    public const INC_SERVICE = '4110';
    public const INC_PARTS = '4120';
    public const INC_ADMISSION = '4130';
    public const COGS = '5110';
    public const DISCOUNT = '5210';

    public function ensureChart(): void
    {
        if (Account::query()->exists()) {
            return;
        }

        // migration seeds; no-op if already migrated
    }

    public function methodAccountCode(string $method): string
    {
        return match ($method) {
            'card' => self::CARD,
            'transfer', 'zarinpal' => self::TRANSFER,
            default => self::CASH,
        };
    }

    /**
     * شناسایی درآمد قبض وقتی مبلغ مشخص شد (دریافتنی = درآمدها).
     * اگر قبلاً سند زده شده و مبلغ عوض شده، سند قبلی حذف و دوباره ساخته می‌شود.
     */
    public function syncReceptionRevenue(Reception $reception): ?JournalEntry
    {
        $reception->loadMissing(['parts']);
        $labor = (int) $reception->labor_cost;
        $parts = (int) $reception->parts_cost;
        $stages = (int) ($reception->stages_cost ?? 0);
        $admission = (int) $reception->admission_fee;
        $discount = (int) $reception->discount;
        // Keep AR aligned with reception.total_amount (includes stages).
        $total = max(0, $labor + $parts + $stages + $admission - $discount);

        $sourceType = 'reception_revenue';
        $existing = JournalEntry::query()
            ->where('source_type', $sourceType)
            ->where('source_id', $reception->id)
            ->first();

        if ($total <= 0) {
            if ($existing) {
                $existing->lines()->delete();
                $existing->delete();
            }

            return null;
        }

        $lines = [];
        $lines[] = [self::RECEIVABLE, $total, 0, 'بدهکار مشتری — '.$reception->ticket_no];
        if ($labor > 0) {
            $lines[] = [self::INC_SERVICE, 0, $labor, 'اجرت تعمیر'];
        }
        if ($stages > 0) {
            $lines[] = [self::INC_SERVICE, 0, $stages, 'مراحل هزینه'];
        }
        if ($parts > 0) {
            $lines[] = [self::INC_PARTS, 0, $parts, 'فروش قطعه'];
        }
        if ($admission > 0) {
            $lines[] = [self::INC_ADMISSION, 0, $admission, 'حق پذیرش'];
        }
        if ($discount > 0) {
            $lines[] = [self::DISCOUNT, $discount, 0, 'تخفیف'];
        }

        return $this->upsertEntry(
            $existing,
            $sourceType,
            $reception->id,
            'شناسایی درآمد قبض '.$reception->ticket_no,
            $reception,
            $lines,
            $reception->customer_id,
            optional($reception->received_at)->toDateString() ?: now()->toDateString()
        );
    }

    public function postPayment(Payment $payment): ?JournalEntry
    {
        $payment->loadMissing(['reception', 'customer']);
        $amount = (int) $payment->amount;
        if ($amount === 0) {
            $this->voidPayment($payment);

            return null;
        }

        // اطمینان از سند درآمد قبل از دریافت
        if ($payment->reception) {
            $this->syncReceptionRevenue($payment->reception->fresh());
        }

        $cashCode = $this->methodAccountCode($payment->method);
        $abs = abs($amount);
        $desc = ($amount > 0 ? 'دریافت ' : 'عودت ').($payment->typeLabel()).' — '.($payment->reception?->ticket_no ?: '');

        if ($amount > 0) {
            $lines = [
                [$cashCode, $abs, 0, $payment->methodLabel()],
                [self::RECEIVABLE, 0, $abs, 'تسویه دریافتنی'],
            ];
        } else {
            $lines = [
                [self::RECEIVABLE, $abs, 0, 'برگشت به دریافتنی'],
                [$cashCode, 0, $abs, $payment->methodLabel()],
            ];
        }

        return $this->upsertEntry(
            JournalEntry::query()->where('source_type', 'payment')->where('source_id', $payment->id)->first(),
            'payment',
            $payment->id,
            $desc,
            $payment->reception,
            $lines,
            $payment->customer_id,
            optional($payment->paid_at)->toDateString()
        );
    }

    /** Remove accounting journal tied to a payment (edit/delete). */
    public function voidPayment(Payment $payment): void
    {
        $entry = JournalEntry::query()
            ->where('source_type', 'payment')
            ->where('source_id', $payment->id)
            ->first();
        if (! $entry) {
            return;
        }
        $entry->lines()->delete();
        $entry->delete();
    }

    public function postReceptionPart(ReceptionPart $part): ?JournalEntry
    {
        $part->loadMissing(['reception', 'part']);
        $qty = (int) $part->quantity;
        $purchase = 0;
        if ($part->part_id && $part->part) {
            $purchase = (int) $part->part->purchase_price * $qty;
        }
        if ($purchase <= 0) {
            // اگر قیمت خرید نداریم، از نصف فی فروش یا صفر — برای انبار حداقل با فی فروش ثبت نکنیم
            // بدون بهای خرید، سند انبار نمی‌زنیم مگر purchase موجود باشد
            return null;
        }

        $lines = [
            [self::COGS, $purchase, 0, $part->part_name],
            [self::INVENTORY, 0, $purchase, 'خروج انبار'],
        ];

        return $this->upsertEntry(
            JournalEntry::query()->where('source_type', 'reception_part')->where('source_id', $part->id)->first(),
            'reception_part',
            $part->id,
            'مصرف قطعه '.$part->part_name.' — '.($part->reception?->ticket_no ?: ''),
            $part->reception,
            $lines
        );
    }

    public function postStockIn(Part $part, int $qty, int $unitPurchase): ?JournalEntry
    {
        $amount = max(0, $qty) * max(0, $unitPurchase);
        if ($amount <= 0) {
            return null;
        }

        // source_id ترکیبی نیست — از id حرکت استفاده نمی‌کنیم؛ کلید یکتا روی stock_in+part+timestamp سخت است
        // برای سادگی: هر بار سند دستی با source_type stock_in و source_id = movement id از بیرون

        return null;
    }

    public function postStockPurchase(int $movementId, Part $part, int $qty, int $unitPurchase, string $note = ''): ?JournalEntry
    {
        $amount = abs($qty) * max(0, $unitPurchase);
        if ($amount <= 0 || $qty === 0) {
            return null;
        }

        if ($qty > 0) {
            // ورود انبار: موجودی بدهکار، صندوق بستانکار (فرض خرید نقدی)
            $lines = [
                [self::INVENTORY, $amount, 0, $part->name],
                [self::CASH, 0, $amount, 'خرید قطعه'],
            ];
            $desc = 'خرید/ورود انبار '.$part->name;
        } else {
            $lines = [
                [self::COGS, $amount, 0, $part->name],
                [self::INVENTORY, 0, $amount, 'خروج انبار'],
            ];
            $desc = 'خروج انبار '.$part->name;
        }

        return $this->upsertEntry(
            JournalEntry::query()->where('source_type', 'stock')->where('source_id', $movementId)->first(),
            'stock',
            $movementId,
            trim($desc.' '.$note),
            null,
            $lines
        );
    }

    /**
     * بازسازی اسناد از داده‌های موجود (یک‌بار / تعمیر).
     */
    public function rebuildFromHistory(): array
    {
        $stats = ['revenue' => 0, 'payments' => 0, 'parts' => 0];

        Reception::query()->orderBy('id')->chunkById(50, function ($rows) use (&$stats) {
            foreach ($rows as $r) {
                if ($this->syncReceptionRevenue($r)) {
                    $stats['revenue']++;
                }
            }
        });

        Payment::query()->orderBy('id')->chunkById(100, function ($rows) use (&$stats) {
            foreach ($rows as $p) {
                if ($this->postPayment($p)) {
                    $stats['payments']++;
                }
            }
        });

        ReceptionPart::query()->with('part')->orderBy('id')->chunkById(100, function ($rows) use (&$stats) {
            foreach ($rows as $rp) {
                if ($this->postReceptionPart($rp)) {
                    $stats['parts']++;
                }
            }
        });

        return $stats;
    }

    /**
     * @param  list<array{0:string,1:int,2:int,3?:string}>  $lines  [code, debit, credit, memo]
     */
    public function createManual(string $description, array $lines, ?string $date = null): JournalEntry
    {
        return $this->writeEntry(null, 'manual', null, $description, null, $lines, null, $date);
    }

    /**
     * @param  list<array{0:string,1:int,2:int,3?:string}>  $lines
     */
    private function upsertEntry(
        ?JournalEntry $existing,
        string $sourceType,
        ?int $sourceId,
        string $description,
        ?Reception $reception,
        array $lines,
        ?int $customerId = null,
        ?string $date = null
    ): JournalEntry {
        if ($existing) {
            $existing->lines()->delete();
            $existing->delete();
        }

        return $this->writeEntry(null, $sourceType, $sourceId, $description, $reception, $lines, $customerId, $date);
    }

    /**
     * @param  list<array{0:string,1:int,2:int,3?:string}>  $lines
     */
    private function writeEntry(
        ?JournalEntry $reuse,
        string $sourceType,
        ?int $sourceId,
        string $description,
        ?Reception $reception,
        array $lines,
        ?int $customerId = null,
        ?string $date = null
    ): JournalEntry {
        $normalized = [];
        $debitSum = 0;
        $creditSum = 0;
        foreach ($lines as $line) {
            $code = $line[0];
            $debit = (int) ($line[1] ?? 0);
            $credit = (int) ($line[2] ?? 0);
            $memo = $line[3] ?? null;
            if ($debit <= 0 && $credit <= 0) {
                continue;
            }
            $account = Account::byCode($code);
            if (! $account) {
                throw ValidationException::withMessages(['account' => "حساب {$code} تعریف نشده است."]);
            }
            $normalized[] = compact('account', 'debit', 'credit', 'memo');
            $debitSum += $debit;
            $creditSum += $credit;
        }

        if ($normalized === [] || $debitSum !== $creditSum) {
            throw ValidationException::withMessages([
                'lines' => 'سند نامتراز است یا سطری ندارد. بدهکار='.$debitSum.' بستانکار='.$creditSum,
            ]);
        }

        return DB::transaction(function () use ($sourceType, $sourceId, $description, $reception, $normalized, $debitSum, $customerId, $date) {
            $entry = JournalEntry::create([
                'entry_no' => JournalEntry::nextEntryNo(),
                'entry_date' => $date ?: ($reception?->updated_at?->toDateString() ?: now()->toDateString()),
                'description' => $description,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'reception_id' => $reception?->id,
                'customer_id' => $customerId ?: $reception?->customer_id,
                'created_by' => Auth::id(),
                'total_amount' => $debitSum,
            ]);

            foreach ($normalized as $row) {
                JournalLine::create([
                    'journal_entry_id' => $entry->id,
                    'account_id' => $row['account']->id,
                    'debit' => $row['debit'],
                    'credit' => $row['credit'],
                    'memo' => $row['memo'],
                ]);
            }

            return $entry->load('lines.account');
        });
    }

    public function accountBalance(Account $account, ?string $from = null, ?string $to = null): array
    {
        $q = JournalLine::query()
            ->where('account_id', $account->id)
            ->whereHas('entry', function ($e) use ($from, $to) {
                if ($from) {
                    $e->whereDate('entry_date', '>=', $from);
                }
                if ($to) {
                    $e->whereDate('entry_date', '<=', $to);
                }
            });

        $debit = (int) (clone $q)->sum('debit');
        $credit = (int) (clone $q)->sum('credit');
        $balance = $account->nature === 'credit' ? ($credit - $debit) : ($debit - $credit);

        return compact('debit', 'credit', 'balance');
    }

    public function trialBalance(?string $from = null, ?string $to = null): array
    {
        $rows = [];
        $sumD = 0;
        $sumC = 0;
        foreach (Account::query()->where('is_active', true)->orderBy('sort_order')->orderBy('code')->get() as $account) {
            $b = $this->accountBalance($account, $from, $to);
            if ($b['debit'] === 0 && $b['credit'] === 0) {
                continue;
            }
            $rows[] = [
                'account' => $account,
                'debit' => $b['debit'],
                'credit' => $b['credit'],
                'balance' => $b['balance'],
            ];
            $sumD += $b['debit'];
            $sumC += $b['credit'];
        }

        return ['rows' => $rows, 'debit' => $sumD, 'credit' => $sumC];
    }
}
