<?php
declare(strict_types=1);

/**
 * Offline license activation for Hesab installs.
 * Key format: base64url(json).base64url(hmac)
 */
final class License
{
    private const PRODUCT = 'HESAB-HDD-2026';

    public static function secret(): string
    {
        $cfg = (string) cfg('license_secret', '');
        if ($cfg !== '') {
            return $cfg;
        }
        // Fallback product pepper (override in config.php for production sales)
        return 'HsbLic#' . self::PRODUCT . '#Kx9mQ2pL';
    }

    public static function fingerprint(): string
    {
        $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
        $base = base_path();
        $db = (string) cfg('db.name', '');
        return substr(hash('sha256', strtolower($host) . '|' . $base . '|' . $db), 0, 16);
    }

    /** @return array<string,mixed> */
    public static function current(): array
    {
        $payload = SettingsStore::getJson('license_payload', []);
        if (!$payload) {
            return self::trialPayload();
        }
        return $payload;
    }

    public static function trialPayload(): array
    {
        return [
            'type' => 'trial',
            'customer' => 'نسخه آزمایشی',
            'domain' => (string) ($_SERVER['HTTP_HOST'] ?? ''),
            'seats' => 3,
            'modules' => ['accounting', 'treasury', 'reports', 'invoices', 'sms'],
            'expires_at' => date('Y-m-d', strtotime('+45 days')),
            'issued_at' => date('Y-m-d'),
            'fingerprint' => self::fingerprint(),
            'status' => 'trial',
        ];
    }

    public static function isValid(): bool
    {
        $lic = self::current();
        if (($lic['status'] ?? '') === 'revoked') {
            return false;
        }
        $exp = (string) ($lic['expires_at'] ?? '');
        if ($exp !== '' && $exp < date('Y-m-d')) {
            return false;
        }
        $fp = (string) ($lic['fingerprint'] ?? '');
        if ($fp !== '' && $fp !== 'ANY' && $fp !== self::fingerprint()) {
            return false;
        }
        return true;
    }

    public static function hasModule(string $code): bool
    {
        if (!self::isValid()) {
            return false;
        }
        $mods = self::current()['modules'] ?? [];
        if (!is_array($mods) || !$mods) {
            return true;
        }
        return in_array($code, $mods, true) || in_array('*', $mods, true);
    }

    public static function statusLabel(): string
    {
        $lic = self::current();
        if (!self::isValid()) {
            $exp = (string) ($lic['expires_at'] ?? '');
            if ($exp !== '' && $exp < date('Y-m-d')) {
                return 'منقضی';
            }
            return 'نامعتبر';
        }
        return ($lic['type'] ?? '') === 'trial' ? 'آزمایشی' : 'فعال';
    }

    /** Issue a key (for sales/admin tooling). */
    public static function issue(array $data): string
    {
        $payload = [
            'type' => $data['type'] ?? 'full',
            'customer' => $data['customer'] ?? '',
            'domain' => $data['domain'] ?? 'ANY',
            'seats' => (int) ($data['seats'] ?? 5),
            'modules' => $data['modules'] ?? ['*'],
            'expires_at' => $data['expires_at'] ?? date('Y-m-d', strtotime('+1 year')),
            'issued_at' => date('Y-m-d'),
            'fingerprint' => $data['fingerprint'] ?? 'ANY',
            'status' => 'active',
            'product' => self::PRODUCT,
        ];
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE);
        $body = self::b64($json);
        $sig = self::b64(hash_hmac('sha256', $json, self::secret(), true));
        return $body . '.' . $sig;
    }

    /** @return array{ok:bool,message:string,payload?:array} */
    public static function activate(string $key): array
    {
        $key = trim($key);
        $parts = explode('.', $key);
        if (count($parts) !== 2) {
            return ['ok' => false, 'message' => 'قالب کلید لایسنس نادرست است'];
        }
        $json = self::ub64($parts[0]);
        $sig = self::ub64($parts[1]);
        if ($json === null || $sig === null) {
            return ['ok' => false, 'message' => 'کلید قابل خواندن نیست'];
        }
        $expect = hash_hmac('sha256', $json, self::secret(), true);
        if (!hash_equals($expect, $sig)) {
            return ['ok' => false, 'message' => 'امضای لایسنس معتبر نیست'];
        }
        $payload = json_decode($json, true);
        if (!is_array($payload)) {
            return ['ok' => false, 'message' => 'محتوای لایسنس خراب است'];
        }
        if (($payload['product'] ?? '') !== self::PRODUCT) {
            return ['ok' => false, 'message' => 'این لایسنس برای محصول دیگری است'];
        }
        $fp = (string) ($payload['fingerprint'] ?? 'ANY');
        if ($fp !== 'ANY' && $fp !== self::fingerprint()) {
            return ['ok' => false, 'message' => 'این لایسنس برای این نصب صادر نشده است'];
        }
        $domain = (string) ($payload['domain'] ?? 'ANY');
        $host = (string) ($_SERVER['HTTP_HOST'] ?? '');
        if ($domain !== 'ANY' && $domain !== '' && !str_contains(strtolower($host), strtolower($domain))) {
            return ['ok' => false, 'message' => 'دامنه لایسنس با این سرور هم‌خوانی ندارد'];
        }
        $payload['status'] = 'active';
        $payload['activated_at'] = date('c');
        $payload['activated_host'] = $host;
        SettingsStore::setJson('license_payload', $payload);
        SettingsStore::set('license_key_last', substr($key, 0, 24) . '…');
        return ['ok' => true, 'message' => 'لایسنس فعال شد', 'payload' => $payload];
    }

    public static function clear(): void
    {
        SettingsStore::set('license_payload', null);
        SettingsStore::set('license_key_last', null);
    }

    /** Soft gate used by UI. */
    public static function guard(string $module = 'accounting'): void
    {
        if (self::isValid() && self::hasModule($module)) {
            return;
        }
        // Allow admins to reach license page; others get flash.
        $path = request_path();
        if (str_starts_with($path, '/settings/license') || $path === '/login' || str_starts_with($path, '/m/login')) {
            return;
        }
        if (Permission::can(current_user(), 'license.manage') || Permission::can(current_user(), 'users.manage')) {
            flash('warn', 'لایسنس نامعتبر یا منقضی است. از منوی سیستم → لایسنس وضعیت را بررسی کنید.');
            return;
        }
    }

    private static function b64(string $bin): string
    {
        return rtrim(strtr(base64_encode($bin), '+/', '-_'), '=');
    }

    private static function ub64(string $txt): ?string
    {
        $pad = 4 - (strlen($txt) % 4);
        if ($pad < 4) {
            $txt .= str_repeat('=', $pad);
        }
        $raw = base64_decode(strtr($txt, '-_', '+/'), true);
        return $raw === false ? null : $raw;
    }
}
