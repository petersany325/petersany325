<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Models\Reception;
use App\Models\SmsLog;
use App\Models\SmsStatusRule;
use Illuminate\Support\Facades\Auth;

class SmsNotificationService
{
    public function __construct(private NiazpardazSmsService $sms)
    {
    }

    public function masterEnabled(): bool
    {
        return AppSetting::getValue('sms_master_enabled', '1') !== '0';
    }

    public function placeholderMap(SmsStatusRule $rule, Reception $reception): array
    {
        $reception->loadMissing(['customer', 'faultType', 'technician']);
        $customer = $reception->customer;
        $device = trim(implode(' ', array_filter([
            $reception->product_name,
            $reception->brand,
            $reception->model,
        ]))) ?: 'دستگاه تعمیری';
        $fault = $reception->reported_fault
            ?: ($reception->final_fault ?: ($reception->faultType?->name ?: '—'));
        $shop = AppSetting::getValue('invoice_shop_name', (string) config('app.name', 'تعمیرگاه'));
        $amount = number_format((int) $reception->total_amount);

        return [
            '{customer_name}' => $customer?->name ?? 'مشتری',
            '{phone}' => $customer?->phone ?? '',
            '{ticket_no}' => $reception->ticket_no ?? '',
            '{receipt_no}' => $reception->receipt_no ?? '',
            '{device}' => $device,
            '{brand}' => $reception->brand ?? '',
            '{model}' => $reception->model ?? '',
            '{serial}' => $reception->serial_number ?: '—',
            '{fault}' => $fault,
            '{status}' => $rule->title,
            '{amount}' => $amount,
            '{price}' => $amount,
            '{shop_name}' => $shop,
            '{technician}' => $reception->technician?->name ?? '—',
            '{approval_url}' => '',
            '{approval_code}' => '',
        ];
    }

    public function renderTemplate(?string $template, SmsStatusRule $rule, Reception $reception): string
    {
        return strtr((string) $template, $this->placeholderMap($rule, $reception));
    }

    public function render(SmsStatusRule $rule, Reception $reception): string
    {
        return $this->renderTemplate($rule->message_template, $rule, $reception);
    }

    public function preview(SmsStatusRule $rule, Reception $reception): string
    {
        return $this->render($rule, $reception);
    }

    /**
     * @return array{ok:bool,skipped?:bool,message:string,log?:SmsLog}
     */
    public function sendForReception(Reception $reception, SmsStatusRule $rule, bool $force = false): array
    {
        if (! $this->masterEnabled()) {
            return ['ok' => false, 'skipped' => true, 'message' => 'ارسال پیامک در سطح سیستم خاموش است.'];
        }

        $mode = $rule->sendMode();
        if (! $force && $mode !== SmsStatusRule::SEND_ALWAYS) {
            $msg = $mode === SmsStatusRule::SEND_ASK
                ? 'ارسال این وضعیت نیاز به تأیید کارمند دارد.'
                : 'ارسال خودکار این وضعیت خاموش است.';

            return ['ok' => false, 'skipped' => true, 'message' => $msg];
        }

        if (! $rule->is_active) {
            return ['ok' => false, 'skipped' => true, 'message' => 'این وضعیت غیرفعال است.'];
        }

        // Hidden rules (مثل مبلغ مشخص شد) فقط با force از تریگرهای خاص ارسال می‌شوند
        if ($rule->is_hidden && ! $force) {
            return ['ok' => false, 'skipped' => true, 'message' => 'این وضعیت مخفی است.'];
        }

        $notes = [];
        $customerOk = null;

        if ($force || $mode === SmsStatusRule::SEND_ALWAYS) {
            $customerOk = $this->dispatch($reception, $rule, 'customer', (string) $rule->message_template);
            $notes[] = ($customerOk['ok'] ?? false)
                ? 'پیامک مشتری ارسال شد'
                : ('پیامک مشتری: '.($customerOk['message'] ?? 'ناموفق'));
        }

        if ($rule->send_coworker && trim((string) $rule->coworker_message_template) !== '') {
            $coworker = $this->dispatch($reception, $rule, 'coworker', (string) $rule->coworker_message_template);
            $notes[] = ($coworker['ok'] ?? false)
                ? 'پیامک همکار ارسال شد'
                : ('پیامک همکار: '.($coworker['message'] ?? 'ناموفق'));
        }

        $ok = (bool) ($customerOk['ok'] ?? false);

        return [
            'ok' => $ok,
            'message' => implode(' | ', $notes),
            'log' => $customerOk['log'] ?? null,
        ];
    }

    private function dispatch(Reception $reception, SmsStatusRule $rule, string $audience, string $template): array
    {
        $reception->loadMissing(['customer', 'technician']);

        if ($audience === 'coworker') {
            $phone = $reception->technician?->phone;
            if (! $phone) {
                return ['ok' => false, 'message' => 'شماره تعمیرکار/همکار موجود نیست.'];
            }
        } else {
            $phone = $reception->customer?->phone;
            if (! $phone) {
                return ['ok' => false, 'message' => 'شماره موبایل مشتری موجود نیست.'];
            }
            if (trim($template) === '') {
                return ['ok' => false, 'skipped' => true, 'message' => 'متن پیامک مشتری خالی است.'];
            }
        }

        $message = $this->renderTemplate($template, $rule, $reception);
        $result = $this->sms->send($phone, $message);

        $log = SmsLog::create([
            'reception_id' => $reception->id,
            'customer_id' => $reception->customer_id,
            'sms_status_rule_id' => $rule->id,
            'sent_by' => Auth::id(),
            'phone' => $phone,
            'status_key' => $rule->status_key,
            'audience' => $audience,
            'message' => $message,
            'ok' => (bool) ($result['ok'] ?? false),
            'provider_message' => $result['message'] ?? null,
        ]);

        return [
            'ok' => (bool) ($result['ok'] ?? false),
            'message' => $result['message'] ?? '',
            'log' => $log,
        ];
    }

    public function sendOnCreate(Reception $reception, bool $force = false): ?array
    {
        $rule = SmsStatusRule::findOnCreate() ?: SmsStatusRule::findForStatus('received');
        if (! $rule) {
            return null;
        }

        if ($rule->sendMode() === SmsStatusRule::SEND_NEVER && ! $force) {
            return ['ok' => false, 'skipped' => true, 'message' => 'ارسال پیامک ثبت قبض خاموش است.'];
        }

        if ($rule->sendMode() === SmsStatusRule::SEND_ASK && ! $force) {
            return ['ok' => false, 'skipped' => true, 'message' => 'ارسال پیامک ثبت قبض نیاز به تأیید کارمند دارد.'];
        }

        return $this->sendForReception($reception, $rule, force: $force || $rule->shouldAutoSend());
    }

    /**
     * Notify customer after ticket fields were edited/corrected.
     *
     * @return array{ok:bool,skipped?:bool,message:string,log?:SmsLog}
     */
    public function sendOnTicketUpdated(Reception $reception, ?string $note = null): array
    {
        if (! $this->masterEnabled()) {
            return ['ok' => false, 'skipped' => true, 'message' => 'ارسال پیامک در سطح سیستم خاموش است.'];
        }

        $reception->loadMissing(['customer', 'faultType', 'technician']);
        $phone = $reception->customer?->phone;
        if (! $phone) {
            return ['ok' => false, 'message' => 'شماره موبایل مشتری موجود نیست.'];
        }

        $device = trim(implode(' ', array_filter([
            $reception->product_name,
            $reception->brand,
            $reception->model,
        ]))) ?: 'دستگاه تعمیری';
        $shop = AppSetting::getValue('invoice_shop_name', (string) config('app.name', 'تعمیرگاه'));
        $lines = [
            $shop,
            'قبض '.$reception->ticket_no.' به‌روزرسانی شد.',
            'وضعیت: '.$reception->statusLabel(),
            'دستگاه: '.$device,
            'سریال: '.($reception->serial_number ?: '—'),
        ];
        if ($reception->hasCostSet()) {
            $lines[] = 'مبلغ: '.number_format((int) $reception->total_amount).' تومان';
        }
        if (trim((string) $note) !== '') {
            $lines[] = trim((string) $note);
        }
        $message = implode("\n", $lines);

        $result = $this->sms->send($phone, $message);
        $log = SmsLog::create([
            'reception_id' => $reception->id,
            'customer_id' => $reception->customer_id,
            'sms_status_rule_id' => null,
            'sent_by' => Auth::id(),
            'phone' => $phone,
            'status_key' => 'ticket_updated',
            'audience' => 'customer',
            'message' => $message,
            'ok' => (bool) ($result['ok'] ?? false),
            'provider_message' => $result['message'] ?? null,
        ]);

        return [
            'ok' => (bool) ($result['ok'] ?? false),
            'message' => $result['message'] ?? (($result['ok'] ?? false) ? 'پیامک به‌روزرسانی ارسال شد.' : 'ارسال پیامک ناموفق بود.'),
            'log' => $log,
        ];
    }

    public function sendOnStatusChange(Reception $reception, string $statusKey, bool $sendSms): ?array
    {
        if (! $sendSms) {
            return ['ok' => false, 'skipped' => true, 'message' => 'ارسال پیامک برای این تغییر وضعیت انتخاب نشد.'];
        }

        $rule = SmsStatusRule::findForStatus($statusKey);
        if (! $rule) {
            return ['ok' => false, 'skipped' => true, 'message' => 'قانون پیامک برای این وضعیت تعریف نشده است.'];
        }

        return $this->sendForReception($reception, $rule, force: true);
    }

    public function sendOnPriceSet(Reception $reception, bool $force = false): ?array
    {
        if (! $reception->hasCostSet()) {
            return ['ok' => false, 'skipped' => true, 'message' => 'مبلغ هنوز مشخص نیست.'];
        }

        // Only auto-create approval links for configured services (surgery/recovery/…)
        $requires = app(CostApprovalService::class)->receptionRequiresApproval($reception);
        if (! $requires && ! $force) {
            // Legacy plain price SMS without approval link
            $rule = SmsStatusRule::findOnPrice();
            if (! $rule) {
                return null;
            }
            if ($rule->sendMode() === SmsStatusRule::SEND_NEVER && ! $rule->on_price) {
                return null;
            }
            if ($rule->sendMode() === SmsStatusRule::SEND_ASK && ! $force) {
                return ['ok' => false, 'skipped' => true, 'message' => 'ارسال پیامک مبلغ نیاز به تأیید کارمند دارد.'];
            }

            return $this->sendForReception($reception, $rule, force: true);
        }

        try {
            $result = app(CostApprovalService::class)->requestAndSend(
                $reception,
                null,
                true,
                force: $force || $requires
            );
            if ($result['ok'] ?? false) {
                return [
                    'ok' => true,
                    'message' => $result['message'] ?? 'لینک تأیید هزینه ارسال شد.',
                    'log' => $result['log'] ?? null,
                    'approval' => $result['approval'] ?? null,
                ];
            }

            return [
                'ok' => false,
                'message' => $result['message'] ?? 'ساخت لینک تأیید ناموفق بود.',
            ];
        } catch (\Throwable $e) {
            // fall through to legacy price SMS
        }

        $rule = SmsStatusRule::findOnPrice();
        if (! $rule) {
            return null;
        }

        if (! $force && $rule->sendMode() === SmsStatusRule::SEND_NEVER && ! $rule->on_price) {
            return null;
        }
        if (! $force && $rule->sendMode() === SmsStatusRule::SEND_ASK) {
            return ['ok' => false, 'skipped' => true, 'message' => 'ارسال پیامک مبلغ نیاز به تأیید کارمند دارد.'];
        }

        return $this->sendForReception($reception, $rule, force: true);
    }

    /**
     * SMS for group delivery — send to pickup phone using delivered template (or custom text).
     */
    public function sendGroupDeliverySms(string $phone, string $pickupName, $receptions): array
    {
        if (! $this->masterEnabled()) {
            return ['ok' => false, 'skipped' => true, 'message' => 'ارسال پیامک خاموش است.'];
        }

        $lines = ["سلام {$pickupName}", 'تحویل گروهی '.AppSetting::getValue('invoice_shop_name', (string) config('app.name', 'تعمیرگاه')).':'];
        $sum = 0;
        foreach ($receptions as $r) {
            $sum += (int) $r->total_amount;
            $lines[] = sprintf(
                '%s | سریال %s | مبلغ %s',
                $r->ticket_no,
                $r->serial_number ?: '—',
                number_format((int) $r->total_amount)
            );
        }
        $lines[] = 'جمع: '.number_format($sum).' تومان';
        $message = implode("\n", $lines);

        $result = $this->sms->send($phone, $message);
        $first = $receptions->first();
        SmsLog::create([
            'reception_id' => $first?->id,
            'customer_id' => $first?->customer_id,
            'sms_status_rule_id' => null,
            'sent_by' => Auth::id(),
            'phone' => $phone,
            'status_key' => 'group_delivery',
            'audience' => 'customer',
            'message' => $message,
            'ok' => (bool) ($result['ok'] ?? false),
            'provider_message' => $result['message'] ?? null,
        ]);

        return [
            'ok' => (bool) ($result['ok'] ?? false),
            'message' => $result['message'] ?? '',
        ];
    }
}
