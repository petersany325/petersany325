<?php

namespace App\Support;

/**
 * Customer-side license plan/status snapshot for admin UI.
 */
class LicenseStatus
{
    public const FILE = 'license_status.json';

    public static function path(): string
    {
        return storage_path('app/'.self::FILE);
    }

    /** @param  array<string, mixed>  $data */
    public static function store(array $data): void
    {
        $payload = array_filter([
            'plan' => $data['plan'] ?? null,
            'plan_code' => $data['plan_code'] ?? null,
            'plan_months' => $data['plan_months'] ?? null,
            'price_toman' => $data['price_toman'] ?? null,
            'activated_at' => $data['activated_at'] ?? null,
            'expires_at' => $data['expires_at'] ?? null,
            'checked_at' => date('c'),
            'ok' => $data['ok'] ?? true,
            'message' => $data['message'] ?? null,
        ], static fn ($v) => $v !== null && $v !== '');

        $dir = dirname(self::path());
        if (! is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        file_put_contents(self::path(), json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }

    /** @return array<string, mixed> */
    public static function current(): array
    {
        $fromFile = [];
        if (is_file(self::path())) {
            $json = json_decode((string) file_get_contents(self::path()), true);
            if (is_array($json)) {
                $fromFile = $json;
            }
        }

        $key = trim((string) config('license.key'));
        if ($key === '') {
            return [
                'enabled' => false,
                'summary' => 'لایسنس مشتری تنظیم نشده (سایت فروشنده)',
            ];
        }

        $plan = (string) ($fromFile['plan'] ?? config('license.plan') ?? '');
        $months = $fromFile['plan_months'] ?? config('license.months');
        $months = ($months === '' || $months === null) ? null : (int) $months;
        $expires = (string) ($fromFile['expires_at'] ?? config('license.expires_at') ?? '');
        $activated = (string) ($fromFile['activated_at'] ?? config('license.activated_at') ?? '');
        $price = (int) ($fromFile['price_toman'] ?? config('license.price') ?? 0);

        $planText = $plan !== '' ? $plan : ($months ? ($months.' ماهه') : 'نامشخص');
        if ($months && $plan === '') {
            $planText = $months.' ماهه';
        } elseif ($months && ! str_contains($plan, 'ماه') && ! str_contains($plan, 'سال') && $plan !== 'مادام‌العمر' && $plan !== 'دمو') {
            $planText = $plan.' ('.$months.' ماه)';
        }

        return [
            'enabled' => true,
            'key' => $key,
            'domain' => (string) config('license.domain'),
            'plan' => $plan,
            'plan_months' => $months,
            'plan_text' => $planText,
            'price_toman' => $price,
            'activated_at' => $activated,
            'expires_at' => $expires,
            'activated_jalali' => $activated !== '' ? jalali_date($activated) : null,
            'expires_jalali' => $expires !== '' ? jalali_date($expires) : null,
            'lifetime' => $expires === '' && ($months === null || $months === 0),
            'summary' => self::summaryLine($planText, $months, $activated, $expires),
            'checked_at' => $fromFile['checked_at'] ?? null,
        ];
    }

    private static function summaryLine(string $planText, ?int $months, string $activated, string $expires): string
    {
        $parts = ['پلن: '.$planText];
        if ($activated !== '') {
            $parts[] = 'شروع: '.jalali_date($activated);
        }
        if ($expires !== '') {
            $parts[] = 'پایان: '.jalali_date($expires);
        } elseif ($months) {
            $parts[] = 'مدت: '.$months.' ماه';
        } else {
            $parts[] = 'مادام‌العمر';
        }

        return implode(' | ', $parts);
    }
}
