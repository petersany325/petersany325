<?php

namespace Plugins\BizCard\src;

use App\Support\SettingsStore;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsSender
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
            } catch (\Throwable) {
                //
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
        $n = static::normalizePhone($phone);

        return (bool) preg_match('/^09\d{9}$/', $n);
    }

    /**
     * @param  array<string,string>  $vars
     * @return array{ok:bool,provider:string,message:string,response:string}
     */
    public static function send(string $phone, string $template, array $vars = [], string $type = 'custom'): array
    {
        $s = CardConfig::get();
        $phone = static::normalizePhone($phone);
        $body = static::render($template, $vars + [
            'link' => CardConfig::publicCardUrl(),
            'card' => CardConfig::publicCardUrl(),
            'brand' => (string) ($s['brand'] ?? 'سرزمین هارد'),
            'phone' => $phone,
        ]);

        if ($phone === '' || ! static::isMobile($phone)) {
            return static::fail('شماره موبایل معتبر نیست.', 'none', $body);
        }
        if (empty($s['sms_enabled'])) {
            ClubStore::logSms($phone, $type, $body, 'disabled', 'none', 'sms_disabled');

            return static::fail('ارسال پیامک در تنظیمات کارت خاموش است.', 'none', $body);
        }

        $provider = (string) ($s['sms_provider'] ?? 'auto');
        $tried = [];

        if ($provider === 'auto' || $provider === 'auth') {
            $r = static::viaAuthCustomers($phone, $body);
            $tried[] = $r;
            if ($r['ok']) {
                ClubStore::logSms($phone, $type, $body, 'sent', $r['provider'], $r['response']);

                return $r;
            }
            if ($provider === 'auth') {
                ClubStore::logSms($phone, $type, $body, 'failed', $r['provider'], $r['response']);

                return $r;
            }
        }

        $resolved = $provider === 'auto' ? static::detectProvider($s) : $provider;
        $r = match ($resolved) {
            'kavenegar' => static::viaKavenegar($phone, $body, $s),
            'ippanel' => static::viaIppanel($phone, $body, $s),
            'melipayamak' => static::viaMelipayamak($phone, $body, $s),
            default => static::fail('پنل پیامک پیکربندی نشده است. کلید API را در تنظیمات کارت ویزیت وارد کنید.', $resolved ?: 'none', $body),
        };
        $tried[] = $r;
        ClubStore::logSms($phone, $type, $body, $r['ok'] ? 'sent' : 'failed', $r['provider'], $r['response']);

        return $r;
    }

    /** @param  array<string,string>  $vars */
    public static function render(string $template, array $vars): string
    {
        $out = $template;
        foreach ($vars as $k => $v) {
            $out = str_replace('{'.$k.'}', (string) $v, $out);
        }

        return trim($out);
    }

    /**
     * @param  array<string,mixed>  $s
     */
    public static function detectProvider(array $s): string
    {
        if (trim((string) ($s['sms_api_key'] ?? '')) !== '') {
            $p = (string) ($s['sms_provider'] ?? 'auto');
            if (in_array($p, ['kavenegar', 'ippanel', 'melipayamak'], true)) {
                return $p;
            }

            return 'kavenegar';
        }

        foreach (['kavenegar_api_key', 'sms_api_key', 'sms_apikey'] as $key) {
            if (trim((string) SettingsStore::get($key, '')) !== '') {
                return 'kavenegar';
            }
        }

        return class_exists(\Plugins\AuthCustomers\src\Services\SmsGateway::class) ? 'auth' : 'none';
    }

    /** @return array{ok:bool,provider:string,message:string,response:string} */
    protected static function viaAuthCustomers(string $phone, string $body): array
    {
        $class = '\\Plugins\\AuthCustomers\\src\\Services\\SmsGateway';
        if (! class_exists($class)) {
            return static::fail('پلاگین پنل اس‌ام‌اس مشتریان پیدا نشد.', 'auth', $body);
        }

        try {
            if (method_exists($class, 'send')) {
                $res = $class::send($phone, $body);

                return static::wrap('auth', $res, $body);
            }
            if (method_exists($class, 'sendSms')) {
                $res = $class::sendSms($phone, $body);

                return static::wrap('auth', $res, $body);
            }
            $gw = new $class();
            foreach (['send', 'sendSms', 'dispatch'] as $m) {
                if (method_exists($gw, $m)) {
                    $res = $gw->{$m}($phone, $body);

                    return static::wrap('auth', $res, $body);
                }
            }
        } catch (\Throwable $e) {
            return static::fail($e->getMessage(), 'auth', $body);
        }

        return static::fail('متد ارسال در پنل اس‌ام‌اس مشتریان پیدا نشد.', 'auth', $body);
    }

    /**
     * @param  array<string,mixed>  $s
     * @return array{ok:bool,provider:string,message:string,response:string}
     */
    protected static function viaKavenegar(string $phone, string $body, array $s): array
    {
        $key = trim((string) ($s['sms_api_key'] ?? ''))
            ?: trim((string) SettingsStore::get('kavenegar_api_key', ''))
            ?: trim((string) SettingsStore::get('sms_api_key', ''));
        $sender = trim((string) ($s['sms_sender'] ?? ''))
            ?: trim((string) SettingsStore::get('kavenegar_sender', ''))
            ?: trim((string) SettingsStore::get('sms_sender', ''));
        if ($key === '') {
            return static::fail('کلید کاوه‌نگار خالی است.', 'kavenegar', $body);
        }

        try {
            $url = 'https://api.kavenegar.com/v1/'.rawurlencode($key).'/sms/send.json';
            $payload = ['receptor' => $phone, 'message' => $body];
            if ($sender !== '') {
                $payload['sender'] = $sender;
            }
            $res = static::httpPost($url, $payload);
            $ok = str_contains($res, '"return"') && (str_contains($res, '"status":200') || str_contains($res, '"status": 200'));

            return [
                'ok' => $ok || $res !== '',
                'provider' => 'kavenegar',
                'message' => $body,
                'response' => mb_substr($res, 0, 500),
            ];
        } catch (\Throwable $e) {
            return static::fail($e->getMessage(), 'kavenegar', $body);
        }
    }

    /**
     * @param  array<string,mixed>  $s
     * @return array{ok:bool,provider:string,message:string,response:string}
     */
    protected static function viaIppanel(string $phone, string $body, array $s): array
    {
        $key = trim((string) ($s['sms_api_key'] ?? ''));
        $sender = trim((string) ($s['sms_sender'] ?? ''));
        if ($key === '') {
            return static::fail('توکن آی‌پی‌پنل خالی است.', 'ippanel', $body);
        }

        try {
            $payload = [
                'sending_type' => 'webservice',
                'from_number' => $sender,
                'message' => $body,
                'params' => [
                    'recipients' => [$phone],
                ],
            ];
            $res = static::httpJson('https://edge.ippanel.com/v1/api/send', $payload, [
                'Authorization' => $key,
                'Content-Type' => 'application/json',
            ]);

            return [
                'ok' => $res !== '',
                'provider' => 'ippanel',
                'message' => $body,
                'response' => mb_substr($res, 0, 500),
            ];
        } catch (\Throwable $e) {
            return static::fail($e->getMessage(), 'ippanel', $body);
        }
    }

    /**
     * @param  array<string,mixed>  $s
     * @return array{ok:bool,provider:string,message:string,response:string}
     */
    protected static function viaMelipayamak(string $phone, string $body, array $s): array
    {
        $user = trim((string) ($s['sms_username'] ?? ''));
        $pass = trim((string) ($s['sms_password'] ?? $s['sms_api_key'] ?? ''));
        $from = trim((string) ($s['sms_sender'] ?? ''));
        if ($user === '' || $pass === '') {
            return static::fail('نام کاربری یا رمز ملی‌پیامک خالی است.', 'melipayamak', $body);
        }

        try {
            $res = static::httpJson('https://rest.payamak-panel.com/api/SendSMS/SendSMS', [
                'username' => $user,
                'password' => $pass,
                'to' => $phone,
                'from' => $from,
                'text' => $body,
                'isflash' => false,
            ]);

            return [
                'ok' => $res !== '',
                'provider' => 'melipayamak',
                'message' => $body,
                'response' => mb_substr($res, 0, 500),
            ];
        } catch (\Throwable $e) {
            return static::fail($e->getMessage(), 'melipayamak', $body);
        }
    }

    /** @param  array<string,mixed>  $payload */
    protected static function httpPost(string $url, array $payload): string
    {
        if (class_exists(Http::class)) {
            $r = Http::asForm()->timeout(12)->post($url, $payload);

            return (string) $r->body();
        }

        return static::curl($url, http_build_query($payload), ['Content-Type: application/x-www-form-urlencoded']);
    }

    /**
     * @param  array<string,mixed>  $payload
     * @param  array<string,string>  $headers
     */
    protected static function httpJson(string $url, array $payload, array $headers = []): string
    {
        if (class_exists(Http::class)) {
            $req = Http::timeout(12)->withHeaders($headers)->asJson();
            $r = $req->post($url, $payload);

            return (string) $r->body();
        }
        $h = ['Content-Type: application/json'];
        foreach ($headers as $k => $v) {
            $h[] = $k.': '.$v;
        }

        return static::curl($url, json_encode($payload, JSON_UNESCAPED_UNICODE) ?: '', $h);
    }

    /** @param  list<string>  $headers */
    protected static function curl(string $url, string $body, array $headers): string
    {
        if (! function_exists('curl_init')) {
            throw new \RuntimeException('امکان ارسال HTTP روی این هاست وجود ندارد.');
        }
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 12,
            CURLOPT_HTTPHEADER => $headers,
        ]);
        $res = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);
        if ($res === false) {
            throw new \RuntimeException($err !== '' ? $err : 'خطای ارسال پیامک');
        }

        return (string) $res;
    }

    /** @return array{ok:bool,provider:string,message:string,response:string} */
    protected static function wrap(string $provider, mixed $res, string $body): array
    {
        if (is_array($res)) {
            $ok = ! empty($res['ok']) || ! empty($res['success']) || (($res['status'] ?? '') === 'sent');
            $text = (string) ($res['message'] ?? $res['response'] ?? json_encode($res, JSON_UNESCAPED_UNICODE));

            return ['ok' => $ok || $text !== '', 'provider' => $provider, 'message' => $body, 'response' => mb_substr($text, 0, 500)];
        }
        if (is_bool($res)) {
            return ['ok' => $res, 'provider' => $provider, 'message' => $body, 'response' => $res ? 'ok' : 'failed'];
        }

        return ['ok' => true, 'provider' => $provider, 'message' => $body, 'response' => mb_substr((string) $res, 0, 500)];
    }

    /** @return array{ok:bool,provider:string,message:string,response:string} */
    protected static function fail(string $error, string $provider, string $body): array
    {
        try {
            if (class_exists(Log::class)) {
                Log::warning('biz-card-sms', ['provider' => $provider, 'error' => $error]);
            }
        } catch (\Throwable) {
            //
        }

        return ['ok' => false, 'provider' => $provider, 'message' => $body, 'response' => $error];
    }
}
