<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Reception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Forces an explicit settlement decision before delivery:
 * - paid: remaining must be 0 (cash/card/transfer collected)
 * - credit: نسیه — deliver while customer stays debtor (receivable)
 * - waive: بخشش مانده با دلیل
 */
class ReceptionSettlementService
{
    public const MODE_PAID = 'paid';
    public const MODE_CREDIT = 'credit';
    public const MODE_WAIVE = 'waive';

    public const MODES = [
        self::MODE_PAID => 'دریافت کامل (نقد / کارت / کارت‌به‌کارت)',
        self::MODE_CREDIT => 'نسیه — بدهکار شدن مشتری',
        self::MODE_WAIVE => 'بخشش مانده / بدون دریافت',
    ];

    public function __construct(
        private readonly ReceptionCustodyGate $gate,
        private readonly AccountingService $accounting,
        private readonly ReceptionLifecycleService $lifecycle,
    ) {}

    /** Persian block reason, or null if delivery settlement is OK. */
    public function deliverySettlementBlock(Reception $reception, ?string $mode = null): ?string
    {
        if ($reception->status === 'delivered') {
            return null;
        }

        if ($block = $this->gate->deliveryBlockReason($reception)) {
            return $block;
        }

        if (! $reception->hasCostSet() && $mode !== self::MODE_WAIVE) {
            return 'قبل از تحویل، هزینه قبض را مشخص کنید یا بخشش/بدون هزینه را با دلیل انتخاب کنید.';
        }

        $remaining = $reception->remainingAmount();
        if ($remaining <= 0) {
            return null;
        }

        if (! in_array($mode, [self::MODE_CREDIT, self::MODE_WAIVE], true)) {
            return 'مانده '.number_format($remaining).' تومان است. قبل از تحویل مشخص کنید: دریافت کامل، نسیه (بدهکار مشتری)، یا بخشش مانده.';
        }

        if ($mode === self::MODE_WAIVE) {
            return null;
        }

        // credit allowed
        return null;
    }

    public function assertCanDeliver(Reception $reception, ?string $mode = null): void
    {
        $reason = $this->deliverySettlementBlock($reception, $mode);
        if ($reason) {
            throw ValidationException::withMessages(['settlement_mode' => $reason]);
        }
    }

    /**
     * Collect payment / credit / waive, then mark delivered.
     *
     * @param  array{
     *   settlement_mode:string,
     *   method?:string,
     *   amount?:int,
     *   note?:string,
     *   pickup_name?:string,
     *   pickup_phone?:string,
     *   send_sms?:bool
     * }  $data
     * @return array{ok:bool,message:string}
     */
    public function settleAndDeliver(Reception $reception, array $data): array
    {
        if ($reception->isDelivered()) {
            return ['ok' => false, 'message' => 'این قبض قبلاً تحویل شده است.'];
        }

        $mode = (string) ($data['settlement_mode'] ?? '');
        if (! array_key_exists($mode, self::MODES)) {
            throw ValidationException::withMessages([
                'settlement_mode' => 'نوع تسویه را انتخاب کنید.',
            ]);
        }

        $note = trim((string) ($data['note'] ?? ''));

        return DB::transaction(function () use ($reception, $data, $mode, $note) {
            $reception = Reception::query()->lockForUpdate()->findOrFail($reception->id);
            $reception->loadMissing(['customer', 'custodyTechnician']);

            if ($mode === self::MODE_PAID) {
                $remaining = $reception->remainingAmount();
                if ($remaining > 0) {
                    $amount = (int) ($data['amount'] ?? $remaining);
                    if ($amount < $remaining) {
                        throw ValidationException::withMessages([
                            'amount' => 'برای دریافت کامل، مبلغ باید برابر مانده ('.number_format($remaining).' تومان) باشد. در غیر این صورت نسیه یا بخشش را انتخاب کنید.',
                        ]);
                    }
                    $method = (string) ($data['method'] ?? 'cash');
                    if (! array_key_exists($method, Payment::METHODS)) {
                        $method = 'cash';
                    }
                    $payment = Payment::create([
                        'reception_id' => $reception->id,
                        'customer_id' => $reception->customer_id,
                        'received_by' => Auth::id(),
                        'type' => 'final',
                        'method' => $method,
                        'amount' => $remaining,
                        'note' => $note !== '' ? $note : 'تسویه هنگام تحویل',
                        'paid_at' => now(),
                    ]);
                    $reception->recalculateTotals();
                    $this->accounting->postPayment($payment->fresh(['reception', 'customer']));
                }
            } elseif ($mode === self::MODE_WAIVE) {
                if ($note === '') {
                    throw ValidationException::withMessages([
                        'note' => 'برای بخشش مانده، دلیل الزامی است.',
                    ]);
                }
                $remaining = $reception->remainingAmount();
                if ($remaining > 0) {
                    $reception->discount = (int) $reception->discount + $remaining;
                    $reception->discount_reason = $note;
                    $reception->save();
                    $reception->recalculateTotals();
                } elseif (! $reception->hasCostSet()) {
                    $reception->confirmCost();
                }
            } elseif ($mode === self::MODE_CREDIT) {
                if ($reception->remainingAmount() <= 0) {
                    $mode = self::MODE_PAID;
                }
                // receivable already tracked via accounting revenue sync
            }

            $reception->refresh();
            $this->assertCanDeliver($reception, $mode === self::MODE_PAID ? null : $mode);

            if (! $reception->hasCostSet()) {
                $reception->confirmCost();
            }

            try {
                $this->accounting->syncReceptionRevenue($reception->fresh());
            } catch (\Throwable) {
            }

            $from = $reception->status;
            $reception->forceFill([
                'status' => 'delivered',
                'delivered_at' => now(),
                'settlement_mode' => $mode,
                'settled_at' => now(),
                'settlement_note' => $note !== '' ? $note : null,
                'pickup_name' => $data['pickup_name'] ?? $reception->pickup_name,
                'pickup_phone' => $data['pickup_phone'] ?? $reception->pickup_phone,
                'delivered_by' => $data['pickup_name'] ?? $reception->delivered_by,
            ])->save();

            $label = self::MODES[$mode] ?? $mode;
            $this->lifecycle->log(
                $reception->fresh(),
                'delivered',
                'delivery',
                $from,
                'تحویل پس از تسویه — '.$label,
                $note !== '' ? $note : $label,
                [
                    'settlement_mode' => $mode,
                    'remaining_at_delivery' => $reception->remainingAmount(),
                ]
            );

            $msg = match ($mode) {
                self::MODE_CREDIT => 'تحویل با نسیه ثبت شد؛ مانده به بدهکاری مشتری منتقل شد.',
                self::MODE_WAIVE => 'تحویل با بخشش مانده ثبت شد.',
                default => 'تسویه و تحویل با موفقیت ثبت شد.',
            };

            return ['ok' => true, 'message' => $msg];
        });
    }
}
