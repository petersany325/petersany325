<?php

namespace App\Support;

use App\Models\AppSetting;

/**
 * Global UI calendar preference (display/input). Storage stays Gregorian.
 */
class CalendarSettings
{
    public const KEY_TYPE = 'calendar_type';

    public const KEY_DIGITS = 'calendar_digits';

    public const TYPE_JALALI = 'jalali';

    public const TYPE_GREGORIAN = 'gregorian';

    public const DIGITS_FA = 'fa';

    public const DIGITS_EN = 'en';

    public static function type(): string
    {
        // Site UI is locked to Jalali; Gregorian remains only for DB storage.
        return self::TYPE_JALALI;
    }

    public static function isJalali(): bool
    {
        return true;
    }

    public static function digits(): string
    {
        try {
            $v = (string) AppSetting::getValue(self::KEY_DIGITS, self::DIGITS_FA);
        } catch (\Throwable) {
            return self::DIGITS_FA;
        }

        return $v === self::DIGITS_EN ? self::DIGITS_EN : self::DIGITS_FA;
    }

    public static function usePersianDigits(): bool
    {
        return self::digits() === self::DIGITS_FA;
    }

    /** @return array{type:string,digits:string,is_jalali:bool} */
    public static function all(): array
    {
        return [
            'type' => self::type(),
            'digits' => self::digits(),
            'is_jalali' => self::isJalali(),
        ];
    }

    public static function save(string $type, string $digits): void
    {
        // Always persist Jalali for UI calendar type.
        AppSetting::setValue(self::KEY_TYPE, self::TYPE_JALALI);
        AppSetting::setValue(
            self::KEY_DIGITS,
            $digits === self::DIGITS_EN ? self::DIGITS_EN : self::DIGITS_FA
        );
    }
}
