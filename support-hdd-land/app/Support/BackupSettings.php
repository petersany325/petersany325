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

    public static function all(): array
    {
        $token = (string) AppSetting::getValue('backup_cron_token', '');
        if ($token === '') {
            $token = Str::random(40);
            AppSetting::setValue('backup_cron_token', $token);
        }

        return [
            'enabled' => AppSetting::getValue('backup_auto_enabled', '0') === '1',
            'scope' => AppSetting::getValue('backup_auto_scope', 'full') ?: 'full',
            'interval' => AppSetting::getValue('backup_auto_interval', 'weekly') ?: 'weekly',
            'weekday' => (int) AppSetting::getValue('backup_auto_weekday', '5'),
            'hour' => (int) AppSetting::getValue('backup_auto_hour', '3'),
            'keep_local' => max(1, (int) AppSetting::getValue('backup_keep_local', '8')),
            'remote_enabled' => AppSetting::getValue('backup_remote_enabled', '0') === '1',
            'remote_protocol' => AppSetting::getValue('backup_remote_protocol', 'ftp') ?: 'ftp',
            'remote_host' => (string) AppSetting::getValue('backup_remote_host', ''),
            'remote_port' => (int) AppSetting::getValue('backup_remote_port', '21'),
            'remote_user' => (string) AppSetting::getValue('backup_remote_user', ''),
            'remote_password' => (string) AppSetting::getValue('backup_remote_password', ''),
            'remote_path' => (string) AppSetting::getValue('backup_remote_path', '/backups'),
            'cron_token' => $token,
            'last_run_at' => AppSetting::getValue('backup_last_run_at'),
            'last_ok' => AppSetting::getValue('backup_last_ok', '') === '1',
            'last_message' => (string) AppSetting::getValue('backup_last_message', ''),
            'last_file' => (string) AppSetting::getValue('backup_last_file', ''),
        ];
    }

    public static function save(array $data): void
    {
        AppSetting::setValue('backup_auto_enabled', ! empty($data['enabled']) ? '1' : '0');
        AppSetting::setValue('backup_auto_scope', in_array($data['scope'] ?? '', array_keys(self::SCOPES), true) ? $data['scope'] : 'full');
        AppSetting::setValue('backup_auto_interval', in_array($data['interval'] ?? '', array_keys(self::INTERVALS), true) ? $data['interval'] : 'weekly');
        AppSetting::setValue('backup_auto_weekday', (string) max(0, min(6, (int) ($data['weekday'] ?? 5))));
        AppSetting::setValue('backup_auto_hour', (string) max(0, min(23, (int) ($data['hour'] ?? 3))));
        AppSetting::setValue('backup_keep_local', (string) max(1, min(60, (int) ($data['keep_local'] ?? 8))));
        AppSetting::setValue('backup_remote_enabled', ! empty($data['remote_enabled']) ? '1' : '0');
        AppSetting::setValue('backup_remote_protocol', in_array($data['remote_protocol'] ?? '', array_keys(self::PROTOCOLS), true) ? $data['remote_protocol'] : 'ftp');
        AppSetting::setValue('backup_remote_host', trim((string) ($data['remote_host'] ?? '')));
        AppSetting::setValue('backup_remote_port', (string) max(1, min(65535, (int) ($data['remote_port'] ?? 21))));
        AppSetting::setValue('backup_remote_user', trim((string) ($data['remote_user'] ?? '')));
        if (array_key_exists('remote_password', $data) && $data['remote_password'] !== null && $data['remote_password'] !== '') {
            AppSetting::setValue('backup_remote_password', (string) $data['remote_password']);
        }
        AppSetting::setValue('backup_remote_path', trim((string) ($data['remote_path'] ?? '/backups')) ?: '/backups');
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
