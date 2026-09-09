<?php

namespace App\Services;

use App\Models\Reception;
use App\Models\ReceptionExitOtp;
use App\Models\SmsLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeliveryExitOtpService
{
    public const TTL_MINUTES = 10;
    public const MAX_ATTEMPTS = 5;
    public const CODE_LENGTH = 5;

    public function __construct(
        private readonly NiazpardazSmsService $sms,
        private readonly SmsNotificationService $smsNotifications,
        private readonly ReceptionLifecycleService $lifecycle,
    ) {}

    public function requiresOtp(Reception $reception): bool
    {
        return (bool) $reception->exit_otp_required;
    }

    public function isVerified(Reception $reception): bool
    {
        return $reception->exit_otp_verified_at !== null;
    }

    public function assertReadyForDelivery(Reception $reception): void
    {
        if (! $this->requiresOtp($reception)) {
            return;
        }
        if ($this->isVerified($reception)) {
            return;
        }

        throw ValidationException::withMessages([
            'exit_otp' => 'برای خروج این قبض، کد تأیید مشتری لازم است. ابتدا کد را ارسال و تأیید کنید (یا مدیر عبور بزند).',
        ]);
    }

    public function setRequired(Reception $reception, bool $required): void
    {
        if ($reception->isDelivered()) {
            throw ValidationException::withMessages([
                'exit_otp_required' => 'قبض تحویل‌شده را نمی‌توان تغییر داد.',
            ]);
        }

        $was = (bool) $reception->exit_otp_required;
        if ($was === $required) {
            return;
        }

        $reception->forceFill([
            'exit_otp_required' => $required,
            // Turning off clears verification; turning on keeps none until code is confirmed.
            'exit_otp_verified_at' => null,
            'exit_otp_bypass_reason' => null,
        ])->save();

        ReceptionExitOtp::query()
            ->where('reception_id', $reception->id)
            ->whereNull('verified_at')
            ->delete();

        $this->lifecycle->log(
            $reception->fresh(),
            $reception->status,
            'exit_otp',
            $reception->status,
            $required ? 'کد تأیید خروج برای این قبض فعال شد' : 'کد تأیید خروج برای این قبض غیرفعال شد',
            null,
            ['exit_otp_required' => $required]
        );
    }

    /**
     * @return array{ok:bool,message:string,expires_at?:string,phone?:string}
     */
    public function send(Reception $reception, ?string $phone = null): array
    {
        if ($reception->isDelivered()) {
            return ['ok' => false, 'message' => 'این قبض قبلاً تحویل شده است.'];
        }

        if (! $this->requiresOtp($reception)) {
            return ['ok' => false, 'message' => 'کد تأیید خروج برای این قبض فعال نیست.'];
        }

        if ($this->isVerified($reception)) {
            return ['ok' => true, 'message' => 'کد قبلاً تأیید شده است.'];
        }

        $phone = $this->normalizePhone($phone
            ?: ($reception->pickup_phone ?: $reception->customer?->phone));
        if (! $phone || strlen($phone) < 10) {
            return ['ok' => false, 'message' => 'موبایل تحویل‌گیرنده / مشتری معتبر نیست.'];
        }

        if (! $this->smsNotifications->masterEnabled()) {
            return ['ok' => false, 'message' => 'ارسال پیامک در تنظیمات خاموش است.'];
        }

        $code = $this->generateCode();
        $expires = now()->addMinutes(self::TTL_MINUTES);

        DB::transaction(function () use ($reception, $phone, $code, $expires) {
            ReceptionExitOtp::query()
                ->where('reception_id', $reception->id)
                ->whereNull('verified_at')
                ->delete();

            ReceptionExitOtp::query()->create([
                'reception_id' => $reception->id,
                'phone' => $phone,
                'code' => $code,
                'expires_at' => $expires,
                'attempts' => 0,
                'created_by' => Auth::id(),
            ]);
        });

        $ticket = $reception->ticket_no ?: ('#'.$reception->id);
        $shop = \App\Models\AppSetting::getValue('invoice_shop_name', (string) config('app.name', 'تعمیرگاه'));
        $message = "{$shop}\nکد تأیید خروج دستگاه برای قبض {$ticket}:\n{$code}\nاعتبار: ".self::TTL_MINUTES.' دقیقه';

        $result = $this->sms->send($phone, $message);

        SmsLog::create([
            'reception_id' => $reception->id,
            'customer_id' => $reception->customer_id,
            'sms_status_rule_id' => null,
            'sent_by' => Auth::id(),
            'phone' => $phone,
            'status_key' => 'exit_otp',
            'audience' => 'customer',
            'message' => $message,
            'ok' => (bool) ($result['ok'] ?? false),
            'provider_message' => $result['message'] ?? null,
        ]);

        $this->lifecycle->log(
            $reception,
            $reception->status,
            'exit_otp',
            $reception->status,
            'ارسال کد تأیید خروج به مشتری',
            null,
            [
                'phone' => $phone,
                'expires_at' => $expires->toDateTimeString(),
                'sms_ok' => (bool) ($result['ok'] ?? false),
            ]
        );

        if (! ($result['ok'] ?? false)) {
            return [
                'ok' => false,
                'message' => 'کد ساخته شد ولی پیامک ارسال نشد: '.($result['message'] ?? 'خطای پنل'),
                'expires_at' => $expires->toDateTimeString(),
                'phone' => $phone,
            ];
        }

        return [
            'ok' => true,
            'message' => 'کد تأیید به '.$phone.' ارسال شد.',
            'expires_at' => $expires->toDateTimeString(),
            'phone' => $phone,
        ];
    }

    /**
     * @return array{ok:bool,message:string}
     */
    public function verify(Reception $reception, string $code): array
    {
        if ($reception->isDelivered()) {
            return ['ok' => false, 'message' => 'این قبض قبلاً تحویل شده است.'];
        }
        if (! $this->requiresOtp($reception)) {
            return ['ok' => false, 'message' => 'کد تأیید خروج برای این قبض فعال نیست.'];
        }
        if ($this->isVerified($reception)) {
            return ['ok' => true, 'message' => 'کد قبلاً تأیید شده است.'];
        }

        $code = preg_replace('/\D+/', '', $code) ?? '';
        if ($code === '') {
            return ['ok' => false, 'message' => 'کد را وارد کنید.'];
        }

        $otp = ReceptionExitOtp::query()
            ->where('reception_id', $reception->id)
            ->whereNull('verified_at')
            ->latest('id')
            ->first();

        if (! $otp) {
            return ['ok' => false, 'message' => 'کدی ارسال نشده است. ابتدا «ارسال کد» را بزنید.'];
        }
        if ($otp->isExpired()) {
            return ['ok' => false, 'message' => 'کد منقضی شده است. دوباره ارسال کنید.'];
        }
        if ((int) $otp->attempts >= self::MAX_ATTEMPTS) {
            return ['ok' => false, 'message' => 'تعداد تلاش بیش از حد است. کد جدید ارسال کنید.'];
        }

        if (! hash_equals((string) $otp->code, $code)) {
            $otp->increment('attempts');

            return ['ok' => false, 'message' => 'کد نادرست است.'];
        }

        DB::transaction(function () use ($reception, $otp) {
            $otp->forceFill(['verified_at' => now()])->save();
            $reception->forceFill([
                'exit_otp_verified_at' => now(),
                'exit_otp_bypass_reason' => null,
            ])->save();
        });

        $this->lifecycle->log(
            $reception->fresh(),
            $reception->status,
            'exit_otp',
            $reception->status,
            'کد تأیید خروج توسط مشتری تأیید شد',
            null,
            ['phone' => $otp->phone, 'verified' => true]
        );

        return ['ok' => true, 'message' => 'کد تأیید شد. می‌توانید تسویه و خروج را ثبت کنید.'];
    }

    /**
     * Manager bypass when customer SMS is unavailable.
     *
     * @return array{ok:bool,message:string}
     */
    public function bypass(Reception $reception, string $reason): array
    {
        $user = Auth::user();
        if (! $user || ! $user->isAdmin()) {
            return ['ok' => false, 'message' => 'فقط مدیر می‌تواند بدون کد عبور بدهد.'];
        }
        if ($reception->isDelivered()) {
            return ['ok' => false, 'message' => 'این قبض قبلاً تحویل شده است.'];
        }
        if (! $this->requiresOtp($reception)) {
            return ['ok' => false, 'message' => 'کد تأیید خروج برای این قبض فعال نیست.'];
        }

        $reason = trim($reason);
        if ($reason === '') {
            return ['ok' => false, 'message' => 'برای عبور بدون کد، دلیل الزامی است.'];
        }

        $reception->forceFill([
            'exit_otp_verified_at' => now(),
            'exit_otp_bypass_reason' => $reason,
        ])->save();

        ReceptionExitOtp::query()
            ->where('reception_id', $reception->id)
            ->whereNull('verified_at')
            ->delete();

        $this->lifecycle->log(
            $reception->fresh(),
            $reception->status,
            'exit_otp',
            $reception->status,
            'عبور مدیر از کد تأیید خروج',
            $reason,
            ['bypass' => true, 'by' => $user->id]
        );

        return ['ok' => true, 'message' => 'عبور مدیر ثبت شد. می‌توانید تسویه و خروج را ثبت کنید.'];
    }

    public function clearForCancel(Reception $reception): void
    {
        $reception->forceFill([
            'exit_otp_verified_at' => null,
            'exit_otp_bypass_reason' => null,
            // keep exit_otp_required as chosen for the ticket
        ])->save();

        ReceptionExitOtp::query()
            ->where('reception_id', $reception->id)
            ->whereNull('verified_at')
            ->delete();
    }

    private function generateCode(): string
    {
        $max = (10 ** self::CODE_LENGTH) - 1;
        $min = 10 ** (self::CODE_LENGTH - 1);

        return (string) random_int($min, $max);
    }

    private function normalizePhone(?string $value): ?string
    {
        if (! $value) {
            return null;
        }
        $map = [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ];
        $digits = preg_replace('/\D+/', '', strtr($value, $map)) ?? '';
        if ($digits === '') {
            return null;
        }
        if (str_starts_with($digits, '98') && strlen($digits) >= 12) {
            $digits = '0'.substr($digits, 2);
        }
        if (strlen($digits) === 10 && str_starts_with($digits, '9')) {
            $digits = '0'.$digits;
        }

        return $digits;
    }
}
