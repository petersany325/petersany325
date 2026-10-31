<?php

namespace App\Support;

use App\Models\AppSetting;

class RemotePartPreorderSettings
{
    public static function all(): array
    {
        $min = max(1, (int) AppSetting::getValue('remote_part_preorder_min_photos', '1'));
        $max = max(1, min(8, (int) AppSetting::getValue('remote_part_preorder_max_photos', '5')));
        if ($max < $min) {
            $max = $min;
        }

        return [
            'enabled' => AppSetting::getValue('remote_part_preorder_enabled', '1') === '1',
            'min_photos' => $min,
            'max_photos' => $max,
            'office_phone' => self::officePhone(),
            'instructions' => (string) AppSetting::getValue(
                'remote_part_preorder_instructions',
                'عکس واضح از رو، پشت و برچسب سریال بگیرید. کد پیش‌سفارش را روی بسته بنویسید.'
            ),
        ];
    }

    public static function isEnabled(): bool
    {
        return self::all()['enabled'];
    }

    public static function officePhone(): string
    {
        $phone = trim((string) AppSetting::getValue('remote_part_preorder_office_phone', '01144447220'));

        return $phone !== '' ? $phone : '01144447220';
    }

    public static function save(array $data): void
    {
        AppSetting::setValue('remote_part_preorder_enabled', ! empty($data['enabled']) ? '1' : '0');
        AppSetting::setValue('remote_part_preorder_min_photos', (string) max(1, (int) ($data['min_photos'] ?? 1)));
        AppSetting::setValue('remote_part_preorder_max_photos', (string) max(1, min(8, (int) ($data['max_photos'] ?? 5))));
        AppSetting::setValue('remote_part_preorder_instructions', trim((string) ($data['instructions'] ?? '')));
        $phone = preg_replace('/\s+/', '', ascii_digits((string) ($data['office_phone'] ?? '01144447220'))) ?? '01144447220';
        AppSetting::setValue('remote_part_preorder_office_phone', $phone !== '' ? $phone : '01144447220');
    }
}
