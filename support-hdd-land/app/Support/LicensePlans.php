<?php

namespace App\Support;

use App\Models\AppSetting;

/**
 * Seller license duration/price plans (editable via AppSetting).
 */
class LicensePlans
{
    public const SETTING_KEY = 'license_plans';

    /**
     * @return array<string, array{code:string,label:string,months:?int,price:int}>
     */
    public static function all(): array
    {
        $defaults = self::defaults();
        $raw = AppSetting::getValue(self::SETTING_KEY);
        if (! is_string($raw) || trim($raw) === '') {
            return $defaults;
        }

        $decoded = json_decode($raw, true);
        if (! is_array($decoded)) {
            return $defaults;
        }

        $out = [];
        foreach ($decoded as $row) {
            if (! is_array($row) || empty($row['code'])) {
                continue;
            }
            $code = (string) $row['code'];
            $out[$code] = [
                'code' => $code,
                'label' => (string) ($row['label'] ?? $code),
                'months' => array_key_exists('months', $row) && $row['months'] !== null && $row['months'] !== ''
                    ? (int) $row['months']
                    : null,
                'price' => (int) ($row['price'] ?? 0),
            ];
        }

        return $out !== [] ? $out : $defaults;
    }

    /**
     * @return array<string, array{code:string,label:string,months:?int,price:int}>
     */
    public static function defaults(): array
    {
        return [
            'm1' => ['code' => 'm1', 'label' => '۱ ماهه', 'months' => 1, 'price' => 0],
            'm3' => ['code' => 'm3', 'label' => '۳ ماهه', 'months' => 3, 'price' => 0],
            'm6' => ['code' => 'm6', 'label' => '۶ ماهه', 'months' => 6, 'price' => 0],
            'y1' => ['code' => 'y1', 'label' => '۱ ساله', 'months' => 12, 'price' => 0],
            'y2' => ['code' => 'y2', 'label' => '۲ ساله', 'months' => 24, 'price' => 0],
            'life' => ['code' => 'life', 'label' => 'مادام‌العمر', 'months' => null, 'price' => 0],
        ];
    }

    /**
     * @param  list<array{code?:string,label?:string,months?:int|null|string,price?:int|string}>  $rows
     */
    public static function save(array $rows): void
    {
        $clean = [];
        foreach ($rows as $row) {
            if (! is_array($row) || empty($row['code']) || empty($row['label'])) {
                continue;
            }
            $months = $row['months'] ?? null;
            if ($months === '' || $months === null) {
                $months = null;
            } else {
                $months = max(0, (int) $months);
                if ($months === 0) {
                    $months = null;
                }
            }
            $clean[] = [
                'code' => preg_replace('/[^a-zA-Z0-9_\-]/', '', (string) $row['code']) ?: 'plan',
                'label' => trim((string) $row['label']),
                'months' => $months,
                'price' => max(0, (int) ($row['price'] ?? 0)),
            ];
        }
        AppSetting::setValue(self::SETTING_KEY, json_encode(array_values($clean), JSON_UNESCAPED_UNICODE));
    }

    /** @return array{code:string,label:string,months:?int,price:int}|null */
    public static function find(string $code): ?array
    {
        $all = self::all();

        return $all[$code] ?? null;
    }

    public static function priceLabel(int $price): string
    {
        if ($price <= 0) {
            return 'قیمت تنظیم نشده';
        }

        return number_format($price).' تومان';
    }
}
