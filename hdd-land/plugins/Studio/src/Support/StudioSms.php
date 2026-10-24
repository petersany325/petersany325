<?php

namespace Plugins\Studio\src\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use ReflectionMethod;
use Throwable;

class StudioSms
{
    public static function normalizePhone(string $phone): string
    {
        if (class_exists(\Plugins\AuthCustomers\src\Services\SmsGateway::class)
            && method_exists(\Plugins\AuthCustomers\src\Services\SmsGateway::class, 'normalizePhone')) {
            try {
                $n = \Plugins\AuthCustomers\src\Services\SmsGateway::normalizePhone($phone);
                if (is_string($n) && $n !== '') {
                    return $n;
                }
            } catch (Throwable) {
            }
        }

        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if (str_starts_with($digits, '0098')) {
            $digits = '0'.substr($digits, 4);
        } elseif (str_starts_with($digits, '98') && strlen($digits) >= 12) {
            $digits = '0'.substr($digits, 2);
        } elseif (str_starts_with($digits, '9') && strlen($digits) === 10) {
            $digits = '0'.$digits;
        }

        return $digits;
    }

    public static function isMobile(string $phone): bool
    {
        return (bool) preg_match('/^09\d{9}$/', self::normalizePhone($phone));
    }

    /** @return list<string> */
    public static function phoneAliases(string $phone): array
    {
        $n = self::normalizePhone($phone);
        $core = ltrim($n, '0');

        return array_values(array_unique(array_filter([
            $n,
            $core,
            '0'.$core,
            '98'.$core,
            '+98'.$core,
        ])));
    }

    /**
     * @return array{ok:bool,message:string}
     */
    public static function sendOtp(string $phone, string $purpose): array
    {
        $phone = self::normalizePhone($phone);
        if (! self::isMobile($phone)) {
            return ['ok' => false, 'message' => 'شماره موبایل معتبر نیست. نمونه درست: 09121234567'];
        }

        $lock = self::lockKey($purpose, $phone);
        if (Cache::has($lock)) {
            return ['ok' => false, 'message' => 'کد قبلی هنوز معتبر است. حدود یک دقیقه صبر کنید و دوباره بزنید.'];
        }

        $hourKey = 'studio.otp.hour.'.$purpose.'.'.$phone;
        $hourCount = (int) Cache::get($hourKey, 0);
        if ($hourCount >= 8) {
            return ['ok' => false, 'message' => 'تعداد پیامک این شماره به حد مجاز ساعت رسید. کمی بعد دوباره تلاش کنید.'];
        }

        if (self::delegateOtpSend($phone, $purpose)) {
            Cache::put($lock, 1, 60);
            Cache::put(self::otpKey($purpose, $phone), ['via' => 'plugin', 'tries' => 0], 300);
            Cache::put($hourKey, $hourCount + 1, 3600);

            return ['ok' => true, 'message' => 'کد تایید به این شماره پیامک شد.'];
        }

        $code = (string) random_int(100000, 999999);
        $sms = self::sendSms($phone, 'کد ورود فراز نت آمل: '.$code);
        if (! $sms['ok']) {
            return $sms;
        }

        Cache::put(self::otpKey($purpose, $phone), [
            'via' => 'local',
            'hash' => Hash::make($code),
            'tries' => 0,
        ], 300);
        Cache::put($lock, 1, 60);
        Cache::put($hourKey, $hourCount + 1, 3600);

        return ['ok' => true, 'message' => 'کد تایید به این شماره پیامک شد.'];
    }

    /**
     * @return array{ok:bool,message:string}
     */
    public static function verifyOtp(string $phone, string $code, string $purpose): array
    {
        $phone = self::normalizePhone($phone);
        $code = preg_replace('/\D+/', '', $code) ?? '';
        if (strlen($code) < 4 || strlen($code) > 8) {
            return ['ok' => false, 'message' => 'کد تایید را کامل وارد کنید.'];
        }

        $key = self::otpKey($purpose, $phone);
        $row = Cache::get($key);
        if (! is_array($row)) {
            return ['ok' => false, 'message' => 'کد منقضی شده. دوباره درخواست بدهید.'];
        }
        if ((int) ($row['tries'] ?? 0) >= 5) {
            Cache::forget($key);

            return ['ok' => false, 'message' => 'تعداد تلاش برای این کد تمام شد. دوباره پیامک بگیرید.'];
        }

        $ok = false;
        if (($row['via'] ?? '') === 'plugin' || self::hasPluginOtp()) {
            $ok = self::delegateOtpVerify($phone, $code, $purpose);
        }
        if (! $ok && ! empty($row['hash'])) {
            $ok = Hash::check($code, (string) $row['hash']);
        }

        if (! $ok) {
            $row['tries'] = (int) ($row['tries'] ?? 0) + 1;
            Cache::put($key, $row, 300);

            return ['ok' => false, 'message' => 'کد تایید نادرست است.'];
        }

        Cache::forget($key);
        Cache::forget(self::lockKey($purpose, $phone));

        return ['ok' => true, 'message' => 'شماره تایید شد.'];
    }

    /**
     * @return array{ok:bool,message:string}
     */
    public static function sendSms(string $phone, string $message): array
    {
        $phone = self::normalizePhone($phone);
        $targets = [
            \Plugins\AuthCustomers\src\Services\SmsGateway::class,
            \Plugins\AuthCustomers\src\Services\SmsSender::class,
        ];
        foreach ($targets as $class) {
            if (! class_exists($class)) {
                continue;
            }
            $result = self::invokeSender($class, $phone, $message);
            if ($result['ok']) {
                return $result;
            }
        }

        return [
            'ok' => false,
            'message' => 'ارسال پیامک الان ممکن نیست. تنظیم درگاه پیامک را در پنل بررسی کنید.',
        ];
    }

    protected static function hasPluginOtp(): bool
    {
        return class_exists(\Plugins\AuthCustomers\src\Services\OtpService::class);
    }

    protected static function delegateOtpSend(string $phone, string $purpose): bool
    {
        $class = \Plugins\AuthCustomers\src\Services\OtpService::class;
        if (! class_exists($class)) {
            return false;
        }
        foreach (['send', 'sendOtp', 'request', 'issue', 'create', 'dispatch'] as $method) {
            $out = self::tryCall($class, $method, [$phone, $purpose], [$phone]);
            if ($out['called'] && self::truthy($out['value'])) {
                return true;
            }
        }

        return false;
    }

    protected static function delegateOtpVerify(string $phone, string $code, string $purpose): bool
    {
        $class = \Plugins\AuthCustomers\src\Services\OtpService::class;
        if (! class_exists($class)) {
            return false;
        }
        foreach (['verify', 'check', 'consume', 'validate', 'confirm'] as $method) {
            $out = self::tryCall($class, $method, [$phone, $code, $purpose], [$phone, $code]);
            if ($out['called'] && self::truthy($out['value'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array{ok:bool,message:string}
     */
    protected static function invokeSender(string $class, string $phone, string $message): array
    {
        foreach (['send', 'sendSms', 'sendMessage', 'otp', 'dispatch'] as $method) {
            $out = self::tryCall($class, $method, [$phone, $message], [$phone, $message, []]);
            if ($out['called'] && self::truthy($out['value'])) {
                return ['ok' => true, 'message' => 'ارسال شد.'];
            }
        }

        return ['ok' => false, 'message' => 'درگاه پیامک پاسخ نداد.'];
    }

    /**
     * @param  list<mixed>  $primary
     * @param  list<mixed>  $fallback
     * @return array{called:bool,value:mixed}
     */
    protected static function tryCall(string $class, string $method, array $primary, array $fallback = []): array
    {
        if (! method_exists($class, $method)) {
            return ['called' => false, 'value' => null];
        }
        try {
            $ref = new ReflectionMethod($class, $method);
            $args = $ref->getNumberOfRequiredParameters() <= count($primary) ? $primary : $fallback;
            $value = $ref->isStatic()
                ? $ref->invoke(null, ...$args)
                : $ref->invoke(new $class, ...$args);

            return ['called' => true, 'value' => $value];
        } catch (Throwable) {
            return ['called' => false, 'value' => null];
        }
    }

    protected static function truthy(mixed $value): bool
    {
        if ($value === null || $value === false) {
            return false;
        }
        if (is_array($value)) {
            if (array_key_exists('ok', $value)) {
                return (bool) $value['ok'];
            }
            if (array_key_exists('success', $value)) {
                return (bool) $value['success'];
            }
        }

        return $value !== '' && $value !== 0 && $value !== '0';
    }

    protected static function otpKey(string $purpose, string $phone): string
    {
        return 'studio.otp.'.$purpose.'.'.$phone;
    }

    protected static function lockKey(string $purpose, string $phone): string
    {
        return 'studio.otp.lock.'.$purpose.'.'.$phone;
    }
}
