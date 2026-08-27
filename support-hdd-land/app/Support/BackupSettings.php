<?php

namespace App\Support;

use App\Models\AppSetting;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class BackupSettings
{
    public const SCOPES = [
        'full' => 'کامل سیستم (مشتری، قبض، کارمند، منو، تنظیمات و …)',
        'accounting' => 'فقط حسابداری و مالی (بکاپ سبک هفتگی)',
    ];

    /** Tables skipped in full dumps (ephemeral / rebuildable on new host). */
    public const SKIP_TABLES = [
        'cache',
        'cache_locks',
        'sessions',
        'jobs',
        'job_batches',
        'failed_jobs',
        'login_otps',
        'password_reset_tokens',
    ];

    public const INTERVALS = [
        'daily' => 'هر روز',
        'weekly' => 'هر هفته',
        'monthly' => 'هر ماه',
    ];

    public const WEEKDAYS = [
        0 => 'یکشنبه',
        1 => 'دوشنبه',
        2 => 'سه‌شنبه',
        3 => 'چهارشنبه',
        4 => 'پنج‌شنبه',
        5 => 'جمعه',
        6 => 'شنبه',
    ];

    public const PROTOCOLS = [
        'ftp' => 'FTP',
        'ftps' => 'FTPS (TLS)',
    ];

    public const CLOUD_KEYS = ['google', 'onedrive', 'mega'];

    public const CLOUD_LABELS = [
        'google' => 'Google Drive (کلود گوگل)',
        'onedrive' => 'OneDrive (کلود مایکروسافت)',
        'mega' => 'MEGA',
    ];

    public static function all(): array
    {
        self::ensureAutoDefaults();

        $token = (string) AppSetting::getValue('backup_cron_token', '');
        if ($token === '') {
            $token = Str::random(40);
            AppSetting::setValue('backup_cron_token', $token);
        }

        return [
            'enabled' => AppSetting::getValue('backup_auto_enabled', '1') === '1',
            'scope' => AppSetting::getValue('backup_auto_scope', 'full') ?: 'full',
            'interval' => AppSetting::getValue('backup_auto_interval', 'daily') ?: 'daily',
            'weekday' => (int) AppSetting::getValue('backup_auto_weekday', '5'),
            'hour' => (int) AppSetting::getValue('backup_auto_hour', '3'),
            'keep_local' => max(1, (int) AppSetting::getValue('backup_keep_local', '14')),
            'remote_enabled' => AppSetting::getValue('backup_remote_enabled', '0') === '1',
            'remote_protocol' => AppSetting::getValue('backup_remote_protocol', 'ftp') ?: 'ftp',
            'remote_host' => (string) AppSetting::getValue('backup_remote_host', ''),
            'remote_port' => (int) AppSetting::getValue('backup_remote_port', '21'),
            'remote_user' => (string) AppSetting::getValue('backup_remote_user', ''),
            'remote_password' => (string) AppSetting::getValue('backup_remote_password', ''),
            'remote_path' => (string) AppSetting::getValue('backup_remote_path', '/backups'),
            'clouds' => [
                'google' => self::cloud('google'),
                'onedrive' => self::cloud('onedrive'),
                'mega' => self::cloud('mega'),
            ],
            'cron_token' => $token,
            'last_run_at' => AppSetting::getValue('backup_last_run_at'),
            'last_ok' => AppSetting::getValue('backup_last_ok', '') === '1',
            'last_message' => (string) AppSetting::getValue('backup_last_message', ''),
            'last_file' => (string) AppSetting::getValue('backup_last_file', ''),
        ];
    }

    /** @return array<string,mixed> */
    public static function cloud(string $key): array
    {
        $key = in_array($key, self::CLOUD_KEYS, true) ? $key : 'google';
        $p = 'backup_cloud_'.$key.'_';

        return [
            'enabled' => AppSetting::getValue($p.'enabled', '0') === '1',
            'client_id' => (string) AppSetting::getValue($p.'client_id', ''),
            'client_secret' => (string) AppSetting::getValue($p.'client_secret', ''),
            'access_token' => (string) AppSetting::getValue($p.'access_token', ''),
            'refresh_token' => (string) AppSetting::getValue($p.'refresh_token', ''),
            'token_expires' => (int) AppSetting::getValue($p.'token_expires', '0'),
            'account_email' => (string) AppSetting::getValue($p.'account_email', ''),
            'folder' => (string) AppSetting::getValue($p.'folder', 'HDDLAND-Backups') ?: 'HDDLAND-Backups',
            'email' => (string) AppSetting::getValue($p.'email', ''),
            'password' => (string) AppSetting::getValue($p.'password', ''),
            'connected' => AppSetting::getValue($p.'connected', '0') === '1',
        ];
    }

    /** Seed safe defaults once: daily full auto-backup for the receipt system. */
    public static function ensureAutoDefaults(): void
    {
        if (AppSetting::query()->where('key', 'backup_auto_enabled')->exists()) {
            return;
        }
        AppSetting::setValue('backup_auto_enabled', '1');
        AppSetting::setValue('backup_auto_scope', 'full');
        AppSetting::setValue('backup_auto_interval', 'daily');
        AppSetting::setValue('backup_auto_weekday', '5');
        AppSetting::setValue('backup_auto_hour', '3');
        AppSetting::setValue('backup_keep_local', '14');
    }

    public static function save(array $data): void
    {
        AppSetting::setValue('backup_auto_enabled', ! empty($data['enabled']) ? '1' : '0');
        AppSetting::setValue('backup_auto_scope', in_array($data['scope'] ?? '', array_keys(self::SCOPES), true) ? $data['scope'] : 'full');
        AppSetting::setValue('backup_auto_interval', in_array($data['interval'] ?? '', array_keys(self::INTERVALS), true) ? $data['interval'] : 'daily');
        AppSetting::setValue('backup_auto_weekday', (string) max(0, min(6, (int) ($data['weekday'] ?? 5))));
        AppSetting::setValue('backup_auto_hour', (string) max(0, min(23, (int) ($data['hour'] ?? 3))));
        AppSetting::setValue('backup_keep_local', (string) max(1, min(60, (int) ($data['keep_local'] ?? 14))));
        AppSetting::setValue('backup_remote_enabled', ! empty($data['remote_enabled']) ? '1' : '0');
        AppSetting::setValue('backup_remote_protocol', in_array($data['remote_protocol'] ?? '', array_keys(self::PROTOCOLS), true) ? $data['remote_protocol'] : 'ftp');
        AppSetting::setValue('backup_remote_host', trim((string) ($data['remote_host'] ?? '')));
        AppSetting::setValue('backup_remote_port', (string) max(1, min(65535, (int) ($data['remote_port'] ?? 21))));
        AppSetting::setValue('backup_remote_user', trim((string) ($data['remote_user'] ?? '')));
        if (array_key_exists('remote_password', $data) && $data['remote_password'] !== null && $data['remote_password'] !== '') {
            AppSetting::setValue('backup_remote_password', (string) $data['remote_password']);
        }
        AppSetting::setValue('backup_remote_path', trim((string) ($data['remote_path'] ?? '/backups')) ?: '/backups');

        foreach (self::CLOUD_KEYS as $key) {
            $cloud = is_array($data['clouds'][$key] ?? null) ? $data['clouds'][$key] : [];
            self::saveCloudConfig($key, $cloud);
        }
    }

    /** @param array<string,mixed> $data */
    public static function saveCloudConfig(string $key, array $data): void
    {
        if (! in_array($key, self::CLOUD_KEYS, true)) {
            return;
        }
        $p = 'backup_cloud_'.$key.'_';
        AppSetting::setValue($p.'enabled', ! empty($data['enabled']) ? '1' : '0');
        if (array_key_exists('client_id', $data)) {
            AppSetting::setValue($p.'client_id', trim((string) $data['client_id']));
        }
        if (array_key_exists('client_secret', $data) && $data['client_secret'] !== null && $data['client_secret'] !== '') {
            AppSetting::setValue($p.'client_secret', (string) $data['client_secret']);
        }
        if (array_key_exists('folder', $data)) {
            AppSetting::setValue($p.'folder', trim((string) $data['folder']) ?: 'HDDLAND-Backups');
        }
        if (array_key_exists('email', $data)) {
            AppSetting::setValue($p.'email', trim((string) $data['email']));
        }
        if (array_key_exists('password', $data) && $data['password'] !== null && $data['password'] !== '') {
            AppSetting::setValue($p.'password', (string) $data['password']);
        }
    }

    /** @param array<string,mixed> $tokens */
    public static function saveCloudTokens(string $key, array $tokens): void
    {
        if (! in_array($key, self::CLOUD_KEYS, true)) {
            return;
        }
        $p = 'backup_cloud_'.$key.'_';
        foreach (['access_token', 'refresh_token', 'account_email'] as $field) {
            if (array_key_exists($field, $tokens)) {
                AppSetting::setValue($p.$field, (string) $tokens[$field]);
            }
        }
        if (array_key_exists('token_expires', $tokens)) {
            AppSetting::setValue($p.'token_expires', (string) (int) $tokens['token_expires']);
        }
        if (array_key_exists('connected', $tokens)) {
            AppSetting::setValue($p.'connected', ! empty($tokens['connected']) ? '1' : '0');
        }
    }

    public static function disconnectCloud(string $key): void
    {
        if (! in_array($key, self::CLOUD_KEYS, true)) {
            return;
        }
        $p = 'backup_cloud_'.$key.'_';
        foreach (['access_token', 'refresh_token', 'account_email', 'token_expires'] as $field) {
            AppSetting::setValue($p.$field, '');
        }
        AppSetting::setValue($p.'connected', '0');
        if ($key === 'mega') {
            // keep email; clear password optional — keep for reconnect
        }
    }

    public static function markResult(bool $ok, string $message, ?string $file = null): void
    {
        AppSetting::setValue('backup_last_run_at', now('Asia/Tehran')->toDateTimeString());
        AppSetting::setValue('backup_last_ok', $ok ? '1' : '0');
        AppSetting::setValue('backup_last_message', mb_substr($message, 0, 500));
        if ($file) {
            AppSetting::setValue('backup_last_file', $file);
        }
    }

    public static function isDue(?Carbon $now = null): bool
    {
        $cfg = self::all();
        if (! $cfg['enabled']) {
            return false;
        }

        $now = ($now ?? now('Asia/Tehran'))->copy()->timezone('Asia/Tehran');
        if ((int) $now->format('G') !== (int) $cfg['hour']) {
            return false;
        }

        if ($cfg['interval'] === 'weekly' && (int) $now->dayOfWeek !== (int) $cfg['weekday']) {
            return false;
        }
        if ($cfg['interval'] === 'monthly' && (int) $now->day !== 1) {
            return false;
        }

        $last = $cfg['last_run_at'] ? Carbon::parse($cfg['last_run_at'], 'Asia/Tehran') : null;
        if ($last && $last->isSameDay($now) && $cfg['last_ok']) {
            return false;
        }

        return true;
    }

    /**
     * Tables for accounting-focused (lightweight) backup.
     * For moving the whole shop to another host, use scope=full instead.
     */
    public static function accountingTables(): array
    {
        return [
            'accounts',
            'journal_entries',
            'journal_lines',
            'payments',
            'gateway_transactions',
            'receptions',
            'reception_parts',
            'reception_cost_stages',
            'reception_status_logs',
            'stock_movements',
            'parts',
            'warehouses',
            'customers',
            'technicians',
            'fault_types',
            'referral_sources',
            'lookup_options',
            'users',
            'app_settings',
            'migrations',
        ];
    }
}
