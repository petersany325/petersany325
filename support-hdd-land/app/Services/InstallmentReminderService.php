<?php

namespace App\Services;

use App\Models\InstallmentItem;
use App\Models\InstallmentPayment;
use App\Models\InstallmentPlan;
use App\Models\SmsLog;
use App\Support\InstallmentSettings;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class InstallmentReminderService
{
    public function __construct(private NiazpardazSmsService $sms) {}

    /**
     * @return array{sent:int,skipped:int,errors:int}
     */
    public function runDueReminders(): array
    {
        $cfg = InstallmentSettings::all();
        $stats = ['sent' => 0, 'skipped' => 0, 'errors' => 0];

        if (! $cfg['enabled'] || ! app(SmsNotificationService::class)->masterEnabled()) {
            return ['sent' => 0, 'skipped' => 0, 'errors' => 0, 'disabled' => true];
        }

        $today = now()->startOfDay();
        $beforeDays = (int) $cfg['before_days'];

        InstallmentItem::query()
            ->with(['plan.customer'])
            ->whereHas('plan', fn ($q) => $q->where('status', 'active'))
            ->whereIn('status', ['pending', 'partial', 'overdue'])
            ->where('paid_amount', '<', \Illuminate\Support\Facades\DB::raw('amount'))
            ->orderBy('id')
            ->chunkById(80, function ($items) use ($cfg, $today, $beforeDays, &$stats) {
                foreach ($items as $item) {
                    /** @var InstallmentItem $item */
                    $plan = $item->plan;
                    $customer = $plan?->customer;
                    if (! $plan || ! $customer) {
                        $stats['skipped']++;
                        continue;
                    }
                    $phone = (string) ($customer->phone ?? '');
                    if ($phone === '') {
                        $stats['skipped']++;
                        continue;
                    }

                    $due = Carbon::parse($item->due_date)->startOfDay();
                    $daysUntil = (int) $today->diffInDays($due, false);
                    $daysAfter = $daysUntil < 0 ? abs($daysUntil) : 0;

                    // Before due
                    if ($beforeDays > 0 && $daysUntil === $beforeDays && ! $item->reminded_before_at) {
                        $ok = $this->dispatch($item, $phone, 'installment_before', $cfg['tpl_before'], [
                            'shop' => shop_name(),
                            'customer' => $customer->displayName(),
                            'seq' => $item->sequence,
                            'amount' => number_format($item->remainingAmount()),
                            'remain' => number_format($item->remainingAmount()),
                            'due' => jalali_date($item->due_date),
                            'days' => $beforeDays,
                        ]);
                        if ($ok) {
                            $item->forceFill(['reminded_before_at' => now()])->save();
                            $stats['sent']++;
                        } else {
                            $stats['errors']++;
                        }
                    }

                    // On due
                    if ($cfg['on_due'] && $daysUntil === 0 && ! $item->reminded_due_at) {
                        $ok = $this->dispatch($item, $phone, 'installment_due', $cfg['tpl_due'], [
                            'shop' => shop_name(),
                            'customer' => $customer->displayName(),
                            'seq' => $item->sequence,
                            'amount' => number_format($item->remainingAmount()),
                            'remain' => number_format($item->remainingAmount()),
                            'due' => jalali_date($item->due_date),
                            'days' => 0,
                        ]);
                        if ($ok) {
                            $item->forceFill(['reminded_due_at' => now()])->save();
                            $stats['sent']++;
                        } else {
                            $stats['errors']++;
                        }
                    }

                    // After due
                    if ($daysAfter > 0) {
                        $sent = $item->after_reminders ?? [];
                        foreach ($cfg['after_days'] as $d) {
                            if ($daysAfter === (int) $d && ! in_array((int) $d, array_map('intval', $sent), true)) {
                                $ok = $this->dispatch($item, $phone, 'installment_after', $cfg['tpl_after'], [
                                    'shop' => shop_name(),
                                    'customer' => $customer->displayName(),
                                    'seq' => $item->sequence,
                                    'amount' => number_format($item->amount),
                                    'remain' => number_format($item->remainingAmount()),
                                    'due' => jalali_date($item->due_date),
                                    'days' => $daysAfter,
                                ]);
                                if ($ok) {
                                    $sent[] = (int) $d;
                                    $item->forceFill(['after_reminders' => array_values(array_unique($sent))])->save();
                                    $stats['sent']++;
                                } else {
                                    $stats['errors']++;
                                }
                            }
                        }
                    }
                }
            });

        return $stats;
    }

    public function sendThanks(InstallmentPayment $payment): void
    {
        try {
            $cfg = InstallmentSettings::all();
            if (! $cfg['enabled'] || ! app(SmsNotificationService::class)->masterEnabled()) {
                return;
            }
            $payment->loadMissing(['item', 'plan.customer']);
            $customer = $payment->plan?->customer;
            $item = $payment->item;
            if (! $customer || ! $item) {
                return;
            }
            $phone = (string) ($customer->phone ?? '');
            if ($phone === '') {
                return;
            }

            $ok = $this->dispatch($item, $phone, 'installment_thanks', $cfg['tpl_thanks'], [
                'shop' => shop_name(),
                'customer' => $customer->displayName(),
                'seq' => $item->sequence,
                'paid' => number_format((int) $payment->amount),
                'amount' => number_format((int) $payment->amount),
                'remain' => number_format($item->remainingAmount()),
                'due' => jalali_date($item->due_date),
                'days' => 0,
            ], $customer->id);

            if ($ok) {
                $payment->forceFill(['thanks_sms_sent' => true])->save();
            }
        } catch (\Throwable $e) {
            Log::warning('installment thanks sms failed: '.$e->getMessage());
        }
    }

    private function dispatch(InstallmentItem $item, string $phone, string $statusKey, string $tpl, array $vars, ?int $customerId = null): bool
    {
        $message = InstallmentSettings::render($tpl, $vars);
        if ($message === '') {
            return false;
        }

        $result = $this->sms->send($phone, $message);
        $ok = (bool) ($result['ok'] ?? false);

        SmsLog::create([
            'reception_id' => $item->plan?->reception_id,
            'customer_id' => $customerId ?? $item->plan?->customer_id,
            'sms_status_rule_id' => null,
            'sent_by' => auth()->id(),
            'phone' => $phone,
            'status_key' => $statusKey,
            'audience' => 'customer',
            'message' => $message,
            'ok' => $ok,
            'provider_message' => $result['message'] ?? null,
        ]);

        return $ok;
    }
}
