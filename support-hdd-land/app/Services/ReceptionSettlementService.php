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
        private readonly DeliveryExitOtpService $exitOtp,
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
     *   confirm_goods_exit?:bool,
     *   accessories_exit_note?:string,
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

        if (empty($data['confirm_goods_exit'])) {
            throw ValidationException::withMessages([
                'confirm_goods_exit' => 'قبل از تحویل، تأیید خروج دستگاه و قطعات همراه الزامی است.',
            ]);
        }

        $note = trim((string) ($data['note'] ?? ''));
        $accessoriesNote = trim((string) ($data['accessories_exit_note'] ?? ''));

        return DB::transaction(function () use ($reception, $data, $mode, $note, $accessoriesNote) {
            $reception = Reception::query()->lockForUpdate()->findOrFail($reception->id);
            $reception->loadMissing(['customer', 'custodyTechnician', 'parts.part']);

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
                $remaining = $reception->remainingAmount();
                if ($remaining <= 0) {
                    $mode = self::MODE_PAID;
                } else {
                    if ($note === '') {
                        throw ValidationException::withMessages([
                            'note' => 'برای تسویه نسیه، توضیح / تعهد پرداخت الزامی است (مثلاً مهلت یا توافق با مشتری).',
                        ]);
                    }
                    // AR already recognized via syncReceptionRevenue (Dr 1210 / Cr income).
                    // Credit delivery keeps remaining unpaid; collection later credits 1210.
                }
            }

            $reception->refresh();
            $this->assertCanDeliver($reception, $mode === self::MODE_PAID ? null : $mode);

            // Optional customer exit OTP — after settlement math, before marking delivered.
            $this->exitOtp->assertReadyForDelivery($reception);

            if (! $reception->hasCostSet()) {
                $reception->confirmCost();
            }

            try {
                $this->accounting->syncReceptionRevenue($reception->fresh());
            } catch (\Throwable) {
            }

            $from = $reception->status;
            $partsExit = $reception->parts->map(fn ($rp) => [
                'name' => $rp->part?->name ?: ($rp->part_name ?? 'قطعه'),
                'qty' => (int) $rp->quantity,
                'total' => (int) $rp->total_price,
            ])->values()->all();

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
            $exitSummary = count($partsExit)
                ? ('خروج '.count($partsExit).' قلم قطعه انبار')
                : 'خروج دستگاه بدون قطعه انبار';
            if ($accessoriesNote !== '') {
                $exitSummary .= ' — '.$accessoriesNote;
            }

            $this->lifecycle->log(
                $reception->fresh(),
                'delivered',
                'delivery',
                $from,
                'تحویل پس از تسویه — '.$label.' | '.$exitSummary,
                $note !== '' ? $note : $label,
                [
                    'settlement_mode' => $mode,
                    'remaining_at_delivery' => $reception->remainingAmount(),
                    'goods_exit_confirmed' => true,
                    'exit_otp_required' => (bool) $reception->exit_otp_required,
                    'exit_otp_verified' => (bool) $reception->exit_otp_verified_at,
                    'exit_otp_bypass' => $reception->exit_otp_bypass_reason,
                    'parts_exit' => $partsExit,
                    'accessories_exit_note' => $accessoriesNote !== '' ? $accessoriesNote : null,
                    'device' => trim(($reception->product_name ?? '').' '.($reception->brand ?? '').' '.($reception->model ?? '')),
                    'serial' => $reception->serial_number,
                ]
            );

            $debt = $reception->fresh()->remainingAmount();
            $msg = match ($mode) {
                self::MODE_CREDIT => 'تسویه نسیه و تحویل ثبت شد. بدهی مشتری: '
                    .number_format($debt).' تومان — در کارتابل مشتری و حساب بدهکاران (۱۲۱۰) دیده می‌شود.',
                self::MODE_WAIVE => 'بخشش مانده، خروج کالا و تحویل ثبت شد.',
                default => 'تسویه، خروج کالا و تحویل با موفقیت ثبت شد.',
            };

            return ['ok' => true, 'message' => $msg];
        });
    }

    /**
     * Collect cash/card/transfer against an open receivable after credit delivery.
     * Journal: Dr cash / Cr 1210 via AccountingService::postPayment — no second revenue.
     *
     * @param  array{method:string,amount:int,note?:string}  $data
     * @return array{ok:bool,message:string}
     */
    public function collectReceivable(Reception $reception, array $data): array
    {
        if ($reception->status === 'cancelled') {
            return ['ok' => false, 'message' => 'قبض لغو شده قابل دریافت نیست.'];
        }

        $remaining = $reception->remainingAmount();
        if ($remaining <= 0) {
            return ['ok' => false, 'message' => 'مانده‌ای برای دریافت نیست.'];
        }

        $method = (string) ($data['method'] ?? 'cash');
        if (! array_key_exists($method, Payment::METHODS) || $method === 'zarinpal') {
            throw ValidationException::withMessages([
                'method' => 'روش دریافت را انتخاب کنید (نقد / کارت / کارت‌به‌کارت).',
            ]);
        }

        $amount = (int) ($data['amount'] ?? 0);
        if ($amount < 1) {
            throw ValidationException::withMessages([
                'amount' => 'مبلغ دریافتی را وارد کنید.',
            ]);
        }
        if ($amount > $remaining) {
            throw ValidationException::withMessages([
                'amount' => 'مبلغ نمی‌تواند از مانده ('.number_format($remaining).' تومان) بیشتر باشد.',
            ]);
        }

        $note = trim((string) ($data['note'] ?? ''));

        return DB::transaction(function () use ($reception, $method, $amount, $note, $remaining) {
            $reception = Reception::query()->lockForUpdate()->findOrFail($reception->id);
            $reception->loadMissing(['customer']);

            $freshRemain = $reception->remainingAmount();
            if ($freshRemain <= 0) {
                return ['ok' => false, 'message' => 'مانده‌ای برای دریافت نیست.'];
            }
            if ($amount > $freshRemain) {
                throw ValidationException::withMessages([
                    'amount' => 'مبلغ نمی‌تواند از مانده ('.number_format($freshRemain).' تومان) بیشتر باشد.',
                ]);
            }

            $type = $amount >= $freshRemain ? 'final' : 'partial';
            $defaultNote = $reception->isDelivered()
                ? 'دریافت بدهی پس از تحویل'
                : 'دریافت مانده قبض';

            $payment = Payment::create([
                'reception_id' => $reception->id,
                'customer_id' => $reception->customer_id,
                'received_by' => Auth::id(),
                'type' => $type,
                'method' => $method,
                'amount' => $amount,
                'note' => $note !== '' ? $note : $defaultNote,
                'paid_at' => now(),
            ]);

            $reception->recalculateTotals();
            $this->accounting->postPayment($payment->fresh(['reception', 'customer']));

            $reception->refresh();
            $left = $reception->remainingAmount();
            $wasCredit = $reception->settlement_mode === self::MODE_CREDIT || $reception->isDelivered();

            if ($left === 0) {
                $reception->forceFill([
                    'settled_at' => now(),
                    'settlement_note' => $note !== ''
                        ? $note
                        : (($reception->settlement_note ? $reception->settlement_note.' | ' : '').'تسویه بدهی پس از دریافت'),
                ])->save();
            }

            $this->lifecycle->log(
                $reception->fresh(),
                $reception->status,
                'debt_collection',
                $reception->status,
                $left === 0
                    ? 'تسویه کامل بدهی — '.number_format($amount).' تومان'
                    : 'دریافت بخشی از بدهی — '.number_format($amount).' تومان',
                $payment->note,
                [
                    'payment_id' => $payment->id,
                    'amount' => $amount,
                    'method' => $method,
                    'remaining_after' => $left,
                    'was_credit' => $wasCredit,
                    'fully_settled' => $left === 0,
                ]
            );

            $msg = $left === 0
                ? 'بدهی قبض کامل تسویه شد. مبلغ '.number_format($amount).' تومان به صندوق نشست (بستانکار ۱۲۱۰).'
                : 'مبلغ '.number_format($amount).' تومان دریافت شد. مانده بدهی: '.number_format($left).' تومان.';

            return ['ok' => true, 'message' => $msg];
        });
    }
}
