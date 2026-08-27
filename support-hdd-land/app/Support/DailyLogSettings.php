<?php

namespace App\Support;

use App\Models\AppSetting;

class DailyLogSettings
{
    public static function allowPastDays(): int
    {
        return max(0, (int) AppSetting::getValue('daily_log_allow_past_days', '7'));
    }

    public static function requireNote(): bool
    {
        return AppSetting::getValue('daily_log_require_note', '0') === '1';
    }

    public static function showQuantity(): bool
    {
        return AppSetting::getValue('daily_log_show_quantity', '1') !== '0';
    }

    public static function save(array $data): void
    {
        AppSetting::setValue('daily_log_allow_past_days', (string) max(0, (int) ($data['allow_past_days'] ?? 7)));
        AppSetting::setValue('daily_log_require_note', ! empty($data['require_note']) ? '1' : '0');
        AppSetting::setValue('daily_log_show_quantity', ! empty($data['show_quantity']) ? '1' : '0');
    }
}
