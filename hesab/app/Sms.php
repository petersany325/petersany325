<?php
declare(strict_types=1);

/**
 * NiazPardaz SMS gateway (classic panel + API-key REST).
 */
final class Sms
{
    public static function config(): array
    {
        return SettingsStore::getMany([
            'sms_enabled',
            'sms_provider',
            'sms_mode',
            'sms_username',
            'sms_password',
            'sms_api_key',
            'sms_from',
            'sms_otp_template',
            'sms_invoice_template',
            'sms_visitor_task',
            'sms_visitor_visit',
            'sms_visitor_commission',
            'sms_base_url',
        ], [
            'sms_enabled' => '0',
            'sms_provider' => 'niazpardaz',
            'sms_mode' => 'classic',
            'sms_username' => '',
            'sms_password' => '',
            'sms_api_key' => '',
            'sms_from' => '',
            'sms_otp_template' => 'کد ورود {app}: {code}',
            'sms_invoice_template' => 'فاکتور شماره {number} به مبلغ {amount} صادر شد.',
            'sms_visitor_task' => '{app}: کارتابل — {title}',
            'sms_visitor_visit' => '{app}: بازدید {date} مشتری {customer} ثبت شد.',
            'sms_visitor_commission' => '{app}: پورسانت فاکتور {invoice} مبلغ {amount} ریال.',
            'sms_base_url' => 'https://panel.niazpardaz-sms.com',
        ]);
    }

    public static function enabled(): bool
    {
        $c = self::config();
        return ($c['sms_enabled'] ?? '0') === '1';
    }

    /** @return array{ok:bool,message:string,raw?:string} */
    public static function send(string $to, string $message): array
    {
        $to = self::normalizeMobile($to);
        if ($to === '') {
            return ['ok' => false, 'message' => 'شماره موبایل نامعتبر است'];
        }
        $c = self::config();
        if (($c['sms_enabled'] ?? '0') !== '1') {
            return ['ok' => false, 'message' => 'ارسال پیامک غیرفعال است'];
        }
        $from = trim($c['sms_from'] ?? '');
        if ($from === '') {
            return ['ok' => false, 'message' => 'شماره فرستنده تنظیم نشده است'];
        }

        $mode = $c['sms_mode'] ?? 'classic';
        if ($mode === 'apikey') {
            return self::sendApiKey($c, $from, $to, $message);
        }
        return self::sendClassic($c, $from, $to, $message);
    }

    public static function sendOtp(string $to, string $code): array
    {
        $c = self::config();
        $tpl = $c['sms_otp_template'] ?: 'کد ورود {app}: {code}';
        $text = strtr($tpl, [
            '{code}' => $code,
            '{app}' => (string) cfg('app_name', 'حساب'),
        ]);
        return self::send($to, $text);
    }

    public static function normalizeMobile(string $mobile): string
    {
        $m = preg_replace('/\D+/', '', $mobile) ?? '';
        if (str_starts_with($m, '98') && strlen($m) === 12) {
            $m = '0' . substr($m, 2);
        }
        if (str_starts_with($m, '9') && strlen($m) === 10) {
            $m = '0' . $m;
        }
        if (!preg_match('/^09\d{9}$/', $m)) {
            return '';
        }
        return $m;
    }

    /** @return array{ok:bool,message:string,raw?:string} */
    private static function sendClassic(array $c, string $from, string $to, string $message): array
    {
        $user = trim($c['sms_username'] ?? '');
        $pass = (string) ($c['sms_password'] ?? '');
        if ($user === '' || $pass === '') {
            return ['ok' => false, 'message' => 'نام کاربری/رمز پنل نیازپرداز خالی است'];
        }
        $base = rtrim($c['sms_base_url'] ?: 'https://panel.niazpardaz-sms.com', '/');
        $url = $base . '/SMSInOutBox/Send';
        $payload = json_encode([
            'UserName' => $user,
            'Password' => $pass,
            'From' => $from,
            'To' => $to,
            'Message' => $message,
        ], JSON_UNESCAPED_UNICODE);
        return self::httpPostJson($url, $payload, []);
    }

    /** @return array{ok:bool,message:string,raw?:string} */
    private static function sendApiKey(array $c, string $from, string $to, string $message): array
    {
        $key = trim($c['sms_api_key'] ?? '');
        if ($key === '') {
            return ['ok' => false, 'message' => 'API Key نیازپرداز خالی است'];
        }
        $url = 'https://login.niazpardaz.ir/api/v2/RestWebApi/SendBatchSms';
        $payload = json_encode([
            'fromNumber' => $from,
            'toNumbers' => $to,
            'messageContent' => $message,
            'isFlash' => false,
            'sendDelay' => 0,
        ], JSON_UNESCAPED_UNICODE);
        return self::httpPostJson($url, $payload, [
            'X-API-Key: ' . $key,
        ]);
    }

    /** @param list<string> $extraHeaders */
    private static function httpPostJson(string $url, string $payload, array $extraHeaders): array
    {
        $headers = array_merge([
            'Content-Type: application/json; charset=UTF-8',
            'Accept: application/json',
        ], $extraHeaders);
        $ctx = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => implode("\r\n", $headers) . "\r\n",
                'content' => $payload,
                'timeout' => 30,
                'ignore_errors' => true,
            ],
            'ssl' => ['verify_peer' => true, 'verify_peer_name' => true],
        ]);
        $raw = @file_get_contents($url, false, $ctx);
        if ($raw === false) {
            return ['ok' => false, 'message' => 'ارتباط با سرویس پیامک برقرار نشد'];
        }
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            // classic panel often returns numeric/string codes
            if (isset($decoded['IsSuccessful']) && $decoded['IsSuccessful']) {
                return ['ok' => true, 'message' => 'ارسال شد', 'raw' => $raw];
            }
            if (isset($decoded['success']) && $decoded['success']) {
                return ['ok' => true, 'message' => 'ارسال شد', 'raw' => $raw];
            }
            if (($decoded['resultCode'] ?? $decoded['ResultCode'] ?? null) === 0) {
                return ['ok' => true, 'message' => 'ارسال شد', 'raw' => $raw];
            }
            $msg = (string) ($decoded['error_message'] ?? $decoded['Message'] ?? $decoded['message'] ?? 'پاسخ ناموفق پنل');
            return ['ok' => false, 'message' => $msg, 'raw' => $raw];
        }
        // Some endpoints return plain "0" or "1"
        $trim = trim($raw);
        if ($trim === '0' || str_contains($trim, 'success') || str_contains($trim, 'Success')) {
            return ['ok' => true, 'message' => 'ارسال شد', 'raw' => $raw];
        }
        return ['ok' => false, 'message' => 'پاسخ ناشناخته پنل: ' . mb_substr($trim, 0, 120), 'raw' => $raw];
    }
}
