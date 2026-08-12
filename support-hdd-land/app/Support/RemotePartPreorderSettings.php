<?php

namespace App\Support;

use App\Models\AppSetting;

class RemotePartPreorderSettings
{
    public static function all(): array
    {
        return [
            'enabled' => AppSetting::getValue('remote_part_preorder_enabled', '1') === '1',
            'min_photos' => max(1, (int) AppSetting::getValue('remote_part_preorder_min_photos', '1')),
            'max_photos' => max(1, min(8, (int) AppSetting::getValue('remote_part_preorder_max_photos', '5'))),
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

    public static function save(array $data): void
    {
        AppSetting::setValue('remote_part_preorder_enabled', ! empty($data['enabled']) ? '1' : '0');
        AppSetting::setValue('remote_part_preorder_min_photos', (string) max(1, (int) ($data['min_photos'] ?? 1)));
        AppSetting::setValue('remote_part_preorder_max_photos', (string) max(1, min(8, (int) ($data['max_photos'] ?? 5))));
        AppSetting::setValue('remote_part_preorder_instructions', trim((string) ($data['instructions'] ?? '')));
    }
}
