<?php

namespace Plugins\AuthCustomers\src\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Plugins\AuthCustomers\Plugin;

class OtpService
{
    /** @return array{ok:bool,code?:string,message:string} */
    public static function issue(?int $userId, string $channel, string $destination, string $purpose): array
    {
        Plugin::ensureSchema();
        $s = Plugin::settings();
        $ttl = (int) ($s['otp_ttl'] ?? 5);
        $length = (int) ($s['otp_length'] ?? 6);
        $cooldown = (int) ($s['otp_resend_cooldown'] ?? 60);
        $rateLimit = (int) ($s['otp_rate_limit_per_hour'] ?? 10);

        $recent = DB::table('auth_otps')
            ->where('destination', $destination)
            ->where('purpose', $purpose)
            ->where('created_at', '>=', now()->subSeconds($cooldown))
            ->orderByDesc('id')
            ->first();
        if ($recent) {
            return ['ok' => false, 'message' => "لطفاً {$cooldown} ثانیه صبر کنید و دوباره درخواست دهید."];
        }

        $hourly = DB::table('auth_otps')
            ->where('destination', $destination)
            ->where('created_at', '>=', now()->subHour())
            ->count();
        if ($hourly >= $rateLimit) {
            return ['ok' => false, 'message' => 'تعداد درخواست کد بیش از حد مجاز است. بعداً تلاش کنید.'];
        }

        $min = (int) str_pad('1', $length, '0');
        $max = (int) str_repeat('9', $length);
        $code = (string) random_int($min, $max);
        $code = str_pad($code, $length, '0', STR_PAD_LEFT);

        DB::table('auth_otps')->insert([
            'user_id' => $userId,
            'channel' => $channel,
            'destination' => $destination,
            'purpose' => $purpose,
            'code' => Hash::make($code),
            'attempts' => 0,
            'expires_at' => now()->addMinutes($ttl),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $replacements = [
            '{code}' => $code,
            '{ttl}' => (string) $ttl,
            '{shop}' => (string) ($s['shop_name_2fa'] ?? 'HDD Land'),
        ];

        if ($channel === 'sms') {
            $patternKey = match ($purpose) {
                'verify_phone' => 'sms_pattern_verify_phone',
                'password_reset' => 'sms_pattern_password_reset',
                'login_2fa' => 'sms_pattern_login_2fa',
                'login_sms' => 'sms_pattern_login_sms',
                default => 'sms_pattern',
            };
            $pattern = trim((string) ($s[$patternKey] ?? ''));
            if ($pattern === '') {
                $pattern = (string) ($s['sms_pattern'] ?? 'کد تأیید شما: {code}');
            }
            $message = strtr($pattern, $replacements);
            $res = SmsGateway::send($destination, $message);
            if (! $res['ok']) {
                return ['ok' => false, 'message' => $res['message']];
            }
        } elseif ($channel === 'email') {
            $pattern = (string) ($s['email_otp_pattern'] ?? 'کد تأیید شما: {code}');
            $message = strtr($pattern, $replacements);
            $subject = (string) ($s['email_otp_subject'] ?? 'کد تأیید ورود');
            try {
                Mail::raw($message, function ($mail) use ($destination, $subject) {
                    $mail->to($destination)->subject($subject);
                });
            } catch (\Throwable $e) {
                Log::info('[EMAIL OTP] to='.$destination.' code='.$code.' err='.$e->getMessage());
            }
        }

        return ['ok' => true, 'code' => $code, 'message' => 'کد تأیید ارسال شد.'];
    }

    public static function verify(?int $userId, string $destination, string $purpose, string $code): bool
    {
        Plugin::ensureSchema();
        $s = Plugin::settings();
        $maxAttempts = (int) ($s['otp_max_attempts'] ?? 5);
        $code = trim($code);

        $q = DB::table('auth_otps')
            ->where('destination', $destination)
            ->where('purpose', $purpose)
            ->whereNull('consumed_at')
            ->where('expires_at', '>', now())
            ->orderByDesc('id');

        if ($userId) {
            $q->where(function ($w) use ($userId) {
                $w->where('user_id', $userId)->orWhereNull('user_id');
            });
        }

        $row = $q->first();
        if (! $row) {
            return false;
        }

        $attempts = (int) ($row->attempts ?? 0);
        if ($attempts >= $maxAttempts) {
            return false;
        }

        $stored = (string) $row->code;
        $ok = str_starts_with($stored, '$2') || str_starts_with($stored, '$argon')
            ? Hash::check($code, $stored)
            : hash_equals($stored, $code);

        if (! $ok) {
            DB::table('auth_otps')->where('id', $row->id)->update([
                'attempts' => $attempts + 1,
                'updated_at' => now(),
            ]);

            return false;
        }

        DB::table('auth_otps')->where('id', $row->id)->update([
            'consumed_at' => now(),
            'updated_at' => now(),
        ]);

        return true;
    }
}
