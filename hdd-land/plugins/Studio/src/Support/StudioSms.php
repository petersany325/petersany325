<?php

namespace Plugins\Studio\src\Support;

use App\Support\SettingsStore;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use ReflectionMethod;
use Throwable;

class StudioSms
{
    public const SETTINGS_KEY = 'studio_niazpardaz';

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
        if (! empty($row['hash'])) {
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
        $np = self::sendNiazpardaz($phone, $message);
        if ($np['ok']) {
            return $np;
        }

        if (self::authCustomersRealDriver()) {
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
        }

        return $np;
    }

    /**
     * @return array{username:string,password:string,from:string,api_key:string,has_password:bool,has_api_key:bool,source:string,ready:bool}
     */
    public static function adminStatus(): array
    {
        $cfg = self::niazpardazConfig();

        return [
            'username' => $cfg['username'],
            'from' => $cfg['from'],
            'has_password' => $cfg['password'] !== '',
            'has_api_key' => $cfg['api_key'] !== '',
            'source' => $cfg['source'],
            'ready' => self::isReady($cfg),
        ];
    }

    /**
     * @param  array<string,mixed>  $data
     */
    public static function saveAdminConfig(array $data): void
    {
        $cur = self::storedConfig();
        $username = trim((string) ($data['username'] ?? $cur['username']));
        $from = self::normalizePhone((string) ($data['from'] ?? $cur['from']));
        if ($from === '') {
            $from = trim((string) ($data['from'] ?? $cur['from']));
        }
        $password = (string) ($data['password'] ?? '');
        if ($password === '') {
            $password = $cur['password'];
        }
        $apiKey = trim((string) ($data['api_key'] ?? ''));
        if ($apiKey === '' || $apiKey === '********') {
            $apiKey = $cur['api_key'];
        }

        SettingsStore::set(self::SETTINGS_KEY, [
            'username' => $username,
            'password' => $password,
            'from' => $from,
            'api_key' => $apiKey,
        ]);

        self::syncAuthCustomersSms($username, $password, $from, $apiKey);
    }

    /**
     * @return array{ok:bool,message:string}
     */
    protected static function sendNiazpardaz(string $phone, string $message): array
    {
        $cfg = self::niazpardazConfig();
        $from = $cfg['from'];
        $user = $cfg['username'];
        $pass = $cfg['password'];
        $key = $cfg['api_key'];

        if ($from === '') {
            return [
                'ok' => false,
                'message' => 'شماره خط نیازپرداز برای ورود سایت تنظیم نشده. از منوی استودیو → پیامک ورود همان خط پنل را ذخیره کنید.',
            ];
        }
        if ($user === '' || $pass === '') {
            if ($key === '') {
                return [
                    'ok' => false,
                    'message' => 'یوزر و رمز پنل نیازپرداز برای ورود سایت تنظیم نشده. از منوی استودیو → پیامک ورود همان اطلاعات پنل قبض را ذخیره کنید.',
                ];
            }
        }

        if ($user !== '' && $pass !== '') {
            $panel = self::niazpardazPanel($user, $pass, $from, $phone, $message);
            if ($panel['ok']) {
                return $panel;
            }
            if ($key !== '') {
                $rest = self::niazpardazRest($from, $phone, $message, $key, $user, $pass);
                if ($rest['ok']) {
                    return $rest;
                }
            }

            return $panel;
        }

        return self::niazpardazRest($from, $phone, $message, $key, $user !== '' ? $user : null, $pass !== '' ? $pass : null);
    }

    /**
     * @return array{ok:bool,message:string}
     */
    protected static function niazpardazPanel(string $username, string $password, string $from, string $to, string $message): array
    {
        try {
            $response = Http::asForm()->timeout(8)->connectTimeout(4)->post('https://panel.niazpardaz-sms.com/SMSInOutBox/Send', [
                'UserName' => $username,
                'Password' => $password,
                'From' => $from,
                'To' => $to,
                'Message' => $message,
            ]);

            if ($response->successful()) {
                $body = trim((string) $response->body());
                if ($body === '' || ! preg_match('/^-\d+$/', $body)) {
                    Log::info('studio sms via niazpardaz panel-post');

                    return ['ok' => true, 'message' => 'پیامک از پنل نیازپرداز ارسال شد.'];
                }
            }

            $get = Http::timeout(20)->get('https://panel.niazpardaz-sms.com/SMSInOutBox/SendSms', [
                'username' => $username,
                'password' => $password,
                'from' => $from,
                'to' => $to,
                'text' => $message,
            ]);

            if ($get->successful()) {
                $body = trim((string) $get->body());
                if ($body === '' || ! preg_match('/^-\d+$/', $body)) {
                    Log::info('studio sms via niazpardaz panel-get');

                    return ['ok' => true, 'message' => 'پیامک از پنل نیازپرداز ارسال شد.'];
                }
            }

            Log::warning('studio niazpardaz panel failed', [
                'status' => $response->status(),
                'get_status' => $get->status(),
            ]);

            return [
                'ok' => false,
                'message' => 'ارسال از پنل نیازپرداز ناموفق بود. یوزر، رمز و خط را در پیامک ورود بررسی کنید.',
            ];
        } catch (Throwable $e) {
            Log::warning('studio niazpardaz panel exception: '.$e->getMessage());

            return ['ok' => false, 'message' => 'ارتباط با پنل نیازپرداز برقرار نشد.'];
        }
    }

    /**
     * @return array{ok:bool,message:string}
     */
    protected static function niazpardazRest(string $from, string $to, string $message, string $apiKey, ?string $username, ?string $password): array
    {
        try {
            $payload = [
                'fromNumber' => $from,
                'toNumbers' => $to,
                'messageContent' => $message,
                'isFlash' => false,
                'sendDelay' => 0,
            ];
            $headers = [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ];
            if ($apiKey !== '') {
                $payload['apiKey'] = $apiKey;
                $headers['X-API-Key'] = $apiKey;
            }
            if ($username) {
                $payload['userName'] = $username;
            }
            if ($password) {
                $payload['password'] = $password;
            }

            $res = Http::withHeaders($headers)->timeout(20)->asJson()
                ->post('https://login.niazpardaz.ir/api/v2/RestWebApi/SendBatchSms', $payload);

            $json = $res->json();
            if (! is_array($json)) {
                $json = [];
            }
            $result = is_array($json['result'] ?? null) ? $json['result'] : $json;
            $code = (int) ($result['resultCode'] ?? $result['ResultCode'] ?? $json['resultCode'] ?? -1);
            $apiOk = in_array($json['success'] ?? null, [true, 1, '1', 'true'], true);
            if ($res->successful() && ($code === 0 || $apiOk)) {
                Log::info('studio sms via niazpardaz rest');

                return ['ok' => true, 'message' => 'پیامک نیازپرداز ارسال شد.'];
            }

            return [
                'ok' => false,
                'message' => 'ارسال REST نیازپرداز ناموفق بود.'.($code >= 0 ? ' کد '.$code : ''),
            ];
        } catch (Throwable $e) {
            Log::warning('studio niazpardaz rest exception: '.$e->getMessage());

            return ['ok' => false, 'message' => 'ارتباط با وب‌سرویس نیازپرداز برقرار نشد.'];
        }
    }

    /**
     * @return array{username:string,password:string,from:string,api_key:string,source:string}
     */
    protected static function niazpardazConfig(): array
    {
        $stored = self::storedConfig();
        $auth = self::authCustomersSettings();

        $username = self::firstFilled([
            $stored['username'],
            env('NIAZPARDAZ_USERNAME'),
            $auth['sms_username'] ?? '',
            SettingsStore::get('niazpardaz_username'),
        ]);
        $password = self::firstFilled([
            $stored['password'],
            env('NIAZPARDAZ_PASSWORD'),
            $auth['sms_password'] ?? '',
            SettingsStore::get('niazpardaz_password'),
        ]);
        $from = self::firstFilled([
            $stored['from'],
            env('NIAZPARDAZ_FROM_NUMBER'),
            env('NIAZPARDAZ_FROM'),
            $auth['sms_sender'] ?? '',
            SettingsStore::get('niazpardaz_from'),
        ]);
        $from = self::normalizePhone($from) ?: $from;
        $apiKey = self::firstFilled([
            $stored['api_key'],
            env('NIAZPARDAZ_API_KEY'),
            $auth['sms_api_key'] ?? '',
            SettingsStore::get('niazpardaz_api_key'),
        ]);

        $source = 'missing';
        if (($stored['username'] !== '' && $stored['password'] !== '') || $stored['from'] !== '') {
            $source = 'studio';
        } elseif (filled(env('NIAZPARDAZ_USERNAME')) || filled(env('NIAZPARDAZ_FROM_NUMBER'))) {
            $source = 'env';
        } elseif (trim((string) ($auth['sms_username'] ?? '')) !== '' || trim((string) ($auth['sms_sender'] ?? '')) !== '') {
            $source = 'auth_customers';
        }

        return [
            'username' => $username,
            'password' => $password,
            'from' => $from,
            'api_key' => $apiKey,
            'source' => $source,
        ];
    }

    /**
     * @return array{username:string,password:string,from:string,api_key:string}
     */
    protected static function storedConfig(): array
    {
        $raw = SettingsStore::get(self::SETTINGS_KEY, []);
        if (is_string($raw) && $raw !== '') {
            $raw = json_decode($raw, true) ?: [];
        }
        if (! is_array($raw)) {
            $raw = [];
        }

        return [
            'username' => trim((string) ($raw['username'] ?? '')),
            'password' => (string) ($raw['password'] ?? ''),
            'from' => trim((string) ($raw['from'] ?? '')),
            'api_key' => trim((string) ($raw['api_key'] ?? '')),
        ];
    }

    /** @return array<string,mixed> */
    protected static function authCustomersSettings(): array
    {
        if (! class_exists(\Plugins\AuthCustomers\Plugin::class)
            || ! method_exists(\Plugins\AuthCustomers\Plugin::class, 'settings')) {
            return [];
        }
        try {
            $s = \Plugins\AuthCustomers\Plugin::settings();

            return is_array($s) ? $s : [];
        } catch (Throwable) {
            return [];
        }
    }

    protected static function authCustomersRealDriver(): bool
    {
        $s = self::authCustomersSettings();
        $driver = (string) ($s['sms_driver'] ?? 'log');

        return in_array($driver, ['kavenegar', 'melipayamak', 'niazpardaz', 'farazsms', 'custom'], true);
    }

    /**
     * @param  array{username:string,password:string,from:string,api_key:string,source?:string}  $cfg
     */
    protected static function isReady(array $cfg): bool
    {
        if (trim($cfg['from'] ?? '') === '') {
            return false;
        }

        return (trim($cfg['username'] ?? '') !== '' && ($cfg['password'] ?? '') !== '')
            || trim($cfg['api_key'] ?? '') !== '';
    }

    protected static function syncAuthCustomersSms(string $username, string $password, string $from, string $apiKey): void
    {
        if (! class_exists(\Plugins\AuthCustomers\Plugin::class)
            || ! method_exists(\Plugins\AuthCustomers\Plugin::class, 'saveSettings')) {
            return;
        }
        try {
            $cur = self::authCustomersSettings();
            \Plugins\AuthCustomers\Plugin::saveSettings(array_merge($cur, [
                'tab' => 'sms',
                'sms_driver' => 'niazpardaz',
                'sms_username' => $username,
                'sms_password' => $password,
                'sms_sender' => $from,
                'sms_api_key' => $apiKey !== '' ? $apiKey : (string) ($cur['sms_api_key'] ?? ''),
                'sms_api_secret' => (string) ($cur['sms_api_secret'] ?? ''),
                'sms_url' => (string) ($cur['sms_url'] ?? ''),
                'sms_pattern' => (string) ($cur['sms_pattern'] ?? 'کد تأیید شما: {code}'),
                'sms_pattern_verify_phone' => (string) ($cur['sms_pattern_verify_phone'] ?? ''),
                'sms_pattern_password_reset' => (string) ($cur['sms_pattern_password_reset'] ?? ''),
                'sms_pattern_login_2fa' => (string) ($cur['sms_pattern_login_2fa'] ?? ''),
                'sms_pattern_login_sms' => (string) ($cur['sms_pattern_login_sms'] ?? ''),
                'sms_timeout' => (int) ($cur['sms_timeout'] ?? 15),
                'sms_phone_format' => (string) ($cur['sms_phone_format'] ?? 'local'),
                'sms_country_code' => (string) ($cur['sms_country_code'] ?? '98'),
            ]));
        } catch (Throwable) {
        }
    }

    /** @param  list<mixed>  $values */
    protected static function firstFilled(array $values): string
    {
        foreach ($values as $value) {
            if ($value === null) {
                continue;
            }
            $value = trim((string) $value);
            if ($value !== '') {
                return $value;
            }
        }

        return '';
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
