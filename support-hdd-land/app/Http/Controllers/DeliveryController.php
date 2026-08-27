<?php

namespace App\Http\Controllers;

use App\Models\DeliveryBatch;
use App\Models\Reception;
use App\Services\AccountingService;
use App\Services\ReceptionCustodyGate;
use App\Services\ReceptionSettlementService;
use App\Services\SmsNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeliveryController extends Controller
{
    public function create()
    {
        return view('deliveries.group', [
            'recent' => DeliveryBatch::query()->withCount('receptions')->latest()->limit(8)->get(),
        ]);
    }

    public function lookup(Request $request)
    {
        $raw = (string) $request->input('tickets', '');
        $tokens = preg_split('/[\s,;]+/u', trim($raw)) ?: [];
        $tokens = array_values(array_filter(array_map('trim', $tokens)));

        if (! $tokens) {
            return response()->json(['ok' => false, 'message' => 'حداقل یک شماره قبض وارد کنید.', 'items' => []]);
        }

        $items = Reception::query()
            ->with('customer')
            ->where(function ($q) use ($tokens) {
                foreach ($tokens as $token) {
                    $q->orWhere('ticket_no', $token)
                        ->orWhere('receipt_no', $token)
                        ->orWhere('ticket_no', 'like', '%'.$token.'%')
                        ->orWhere('receipt_no', 'like', '%'.$token.'%');
                }
            })
            ->limit(40)
            ->get()
            ->unique('id')
            ->values();

        $gate = app(ReceptionCustodyGate::class);
        $payload = $items->map(function (Reception $r) use ($gate) {
            $block = $gate->deliveryBlockReason($r);

            return [
                'id' => $r->id,
                'ticket_no' => $r->ticket_no,
                'receipt_no' => $r->receipt_no,
                'customer' => $r->customer?->name,
                'phone' => $r->customer?->phone,
                'serial' => $r->serial_number,
                'device' => trim(($r->product_name ?? '').' '.($r->brand ?? '').' '.($r->model ?? '')),
                'status' => $r->status,
                'status_label' => $r->statusLabel(),
                'custody' => $r->custodyLabel(),
                'total_amount' => (int) $r->total_amount,
                'paid_amount' => (int) $r->paid_amount,
                'remaining' => $r->remainingAmount(),
                'labor_cost' => (int) $r->labor_cost,
                'parts_cost' => (int) $r->parts_cost,
                'has_cost' => $r->hasCostSet(),
                'already_delivered' => $r->status === 'delivered',
                'custody_ok' => $block === null,
                'custody_block' => $block,
            ];
        });

        return response()->json([
            'ok' => true,
            'count' => $payload->count(),
            'missing_cost' => $payload->where('has_cost', false)->count(),
            'unsettled' => $payload->where('remaining', '>', 0)->where('already_delivered', false)->count(),
            'custody_blocked' => $payload->where('custody_ok', false)->where('already_delivered', false)->count(),
            'items' => $payload,
        ]);
    }

    public function store(Request $request, SmsNotificationService $sms, ReceptionCustodyGate $gate, ReceptionSettlementService $settlement)
    {
        $data = $request->validate([
            'pickup_name' => ['required', 'string', 'max:120'],
            'pickup_phone' => ['required', 'string', 'max:20'],
            'ticket_ids' => ['required', 'array', 'min:1'],
            'ticket_ids.*' => ['integer', 'exists:receptions,id'],
            'costs' => ['nullable', 'array'],
            'costs.*' => ['nullable', 'integer', 'min:0'],
            'force_without_cost' => ['nullable', 'boolean'],
            'settlement_mode' => ['nullable', 'in:paid,credit,waive'],
            'send_sms' => ['nullable', 'boolean'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $phone = $this->normalizePhone($data['pickup_phone']);
        if (strlen($phone) < 10) {
            throw ValidationException::withMessages(['pickup_phone' => 'موبایل معتبر نیست.']);
        }

        $receptions = Reception::query()
            ->with('customer')
            ->whereIn('id', $data['ticket_ids'])
            ->get();

        if ($receptions->isEmpty()) {
            throw ValidationException::withMessages(['ticket_ids' => 'قبضی پیدا نشد.']);
        }

        // Apply inline costs if provided
        foreach ($receptions as $r) {
            if (isset($data['costs'][$r->id]) && (int) $data['costs'][$r->id] >= 0) {
                $amount = (int) $data['costs'][$r->id];
                if ($amount > 0) {
                    $gate->assertCanSetCost($r);
                }
                $r->labor_cost = $amount;
                $r->save();
                $r->recalculateTotals();
                if ($amount > 0) {
                    $r->confirmCost();
                }
                try {
                    app(AccountingService::class)->syncReceptionRevenue($r->fresh());
                } catch (\Throwable $e) {
                }
            }
        }

        // reload after cost updates
        $receptions = Reception::query()
            ->with(['customer', 'custodyTechnician'])
            ->whereIn('id', $receptions->pluck('id'))
            ->get();

        $custodyBlocked = $receptions->filter(function (Reception $r) use ($gate) {
            return $r->status !== 'delivered' && $gate->deliveryBlockReason($r);
        });
        if ($custodyBlocked->isNotEmpty()) {
            $msgs = $custodyBlocked->map(fn (Reception $r) => $r->ticket_no.': '.$gate->deliveryBlockReason($r))->implode(' | ');

            throw ValidationException::withMessages([
                'ticket_ids' => $msgs,
            ]);
        }

        $missing = $receptions->filter(fn (Reception $r) => ! $r->hasCostSet() && $r->status !== 'delivered');
        $mode = $data['settlement_mode'] ?? null;
        if ($missing->isNotEmpty()) {
            if ($mode !== ReceptionSettlementService::MODE_WAIVE && ! $request->boolean('force_without_cost')) {
                $list = $missing->pluck('ticket_no')->implode('، ');

                throw ValidationException::withMessages([
                    'ticket_ids' => "هزینه این قبض‌ها مشخص نیست: {$list}. مبلغ را ثبت کنید یا تسویه گروهی «بخشش» را انتخاب کنید.",
                ]);
            }
            if ($mode !== ReceptionSettlementService::MODE_WAIVE) {
                $mode = ReceptionSettlementService::MODE_WAIVE;
            }
            if (trim((string) ($data['note'] ?? '')) === '') {
                throw ValidationException::withMessages([
                    'note' => 'برای تحویل بدون هزینه / بخشش، یادداشت دلیل الزامی است.',
                ]);
            }
        }

        $unsettled = $receptions->filter(fn (Reception $r) => $r->status !== 'delivered' && $r->remainingAmount() > 0);
        if ($unsettled->isNotEmpty()) {
            if (! in_array($mode, [ReceptionSettlementService::MODE_CREDIT, ReceptionSettlementService::MODE_WAIVE], true)) {
                $list = $unsettled->map(fn (Reception $r) => $r->ticket_no.' (مانده '.number_format($r->remainingAmount()).')')->implode('، ');

                throw ValidationException::withMessages([
                    'settlement_mode' => "قبل از تحویل گروهی، تسویه را مشخص کنید. قبض‌های تسویه‌نشده: {$list}. گزینه نسیه یا بخشش را انتخاب کنید، یا ابتدا در صفحه هر قبض دریافت کامل ثبت کنید.",
                ]);
            }
            if ($mode === ReceptionSettlementService::MODE_WAIVE && trim((string) ($data['note'] ?? '')) === '') {
                throw ValidationException::withMessages([
                    'note' => 'برای بخشش مانده در تحویل گروهی، دلیل الزامی است.',
                ]);
            }
        }

        $batch = DB::transaction(function () use ($data, $phone, $receptions, $settlement, $mode) {
            $batch = DeliveryBatch::create([
                'batch_code' => DeliveryBatch::nextCode(),
                'pickup_name' => $data['pickup_name'],
                'pickup_phone' => $phone,
                'ticket_count' => $receptions->count(),
                'total_amount' => $receptions->sum('total_amount'),
                'note' => $data['note'] ?? null,
                'created_by' => Auth::id(),
                'delivered_at' => now(),
            ]);

            foreach ($receptions as $r) {
                if ($r->status === 'delivered') {
                    continue;
                }

                $ticketMode = $mode;
                if ($r->remainingAmount() <= 0 && $r->hasCostSet()) {
                    $ticketMode = ReceptionSettlementService::MODE_PAID;
                } elseif (! $ticketMode) {
                    $ticketMode = ReceptionSettlementService::MODE_PAID;
                }

                $result = $settlement->settleAndDeliver($r, [
                    'settlement_mode' => $ticketMode,
                    'note' => $data['note'] ?? null,
                    'pickup_name' => $data['pickup_name'],
                    'pickup_phone' => $phone,
                    'confirm_goods_exit' => true,
                    'accessories_exit_note' => $data['note'] ?? null,
                ]);
                if (! ($result['ok'] ?? false)) {
                    throw ValidationException::withMessages([
                        'ticket_ids' => ($r->ticket_no).': '.($result['message'] ?? 'خطا در تحویل'),
                    ]);
                }

                $r->refresh();
                $r->forceFill(['delivery_batch_id' => $batch->id])->save();
            }

            return $batch;
        });

        $smsNote = '';
        if ($request->boolean('send_sms', true)) {
            $result = $sms->sendGroupDeliverySms($phone, $data['pickup_name'], $receptions);
            $batch->update(['sms_sent' => (bool) ($result['ok'] ?? false)]);
            $smsNote = ($result['ok'] ?? false)
                ? ' پیامک تحویل ارسال شد.'
                : ' پیامک ارسال نشد: '.($result['message'] ?? '');
        }

        return redirect()
            ->route('deliveries.group')
            ->with('success', "تحویل گروهی {$batch->batch_code} — {$receptions->count()} قبض ثبت شد.{$smsNote}");
    }

    private function normalizePhone(string $value): string
    {
        $map = [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ];
        $digits = preg_replace('/\D+/', '', strtr($value, $map)) ?? '';
        if (str_starts_with($digits, '98') && strlen($digits) >= 12) {
            $digits = '0'.substr($digits, 2);
        }
        if (strlen($digits) === 10 && str_starts_with($digits, '9')) {
            $digits = '0'.$digits;
        }

        return $digits;
    }
}
