<?php

namespace App\Support;

use App\Models\AppSetting;
use Carbon\Carbon;

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

    public static function minEntriesPerDay(): int
    {
        return max(1, min(20, (int) AppSetting::getValue('daily_log_min_entries', '1')));
    }

    public static function skipFridays(): bool
    {
        return AppSetting::getValue('daily_log_skip_fridays', '1') !== '0';
    }

    /** @return list<string> Y-m-d */
    public static function closedDates(): array
    {
        $raw = trim((string) AppSetting::getValue('daily_log_closed_dates', ''));
        if ($raw === '') {
            return [];
        }

        $out = [];
        foreach (preg_split('/[\s,;]+/', $raw) ?: [] as $piece) {
            $piece = trim($piece);
            if ($piece === '') {
                continue;
            }
            $parsed = function_exists('parse_jalali_or_gregorian_date')
                ? parse_jalali_or_gregorian_date($piece)
                : null;
            if ($parsed) {
                $out[] = Carbon::parse($parsed, 'Asia/Tehran')->toDateString();
            } elseif (preg_match('/^\d{4}-\d{2}-\d{2}$/', $piece)) {
                $out[] = $piece;
            }
        }

        return array_values(array_unique($out));
    }

    public static function closedDatesRaw(): string
    {
        return (string) AppSetting::getValue('daily_log_closed_dates', '');
    }

    /** Closed dates as Jalali lines for the settings textarea. */
    public static function closedDatesDisplay(): string
    {
        $dates = self::closedDates();
        if ($dates === []) {
            return self::closedDatesRaw();
        }

        return implode("\n", array_map(static function (string $day): string {
            return function_exists('jalali_input') ? (jalali_input($day) ?: $day) : $day;
        }, $dates));
    }

    public static function isExemptDay(Carbon $date): bool
    {
        $day = $date->copy()->timezone('Asia/Tehran')->startOfDay();
        if (self::skipFridays() && (int) $day->dayOfWeek === Carbon::FRIDAY) {
            return true;
        }

        return in_array($day->toDateString(), self::closedDates(), true);
    }

    public static function save(array $data): void
    {
        AppSetting::setValue('daily_log_allow_past_days', (string) max(0, (int) ($data['allow_past_days'] ?? 7)));
        AppSetting::setValue('daily_log_require_note', ! empty($data['require_note']) ? '1' : '0');
        AppSetting::setValue('daily_log_show_quantity', ! empty($data['show_quantity']) ? '1' : '0');
        AppSetting::setValue('daily_log_min_entries', (string) max(1, min(20, (int) ($data['min_entries'] ?? 1))));
        AppSetting::setValue('daily_log_skip_fridays', ! empty($data['skip_fridays']) ? '1' : '0');

        $closed = trim((string) ($data['closed_dates'] ?? ''));
        // Normalize to Jalali lines when possible (storage text is display-friendly).
        if ($closed !== '' && function_exists('parse_jalali_or_gregorian_date') && function_exists('jalali_input')) {
            $lines = [];
            foreach (preg_split('/[\s,;]+/', $closed) ?: [] as $piece) {
                $piece = trim($piece);
                if ($piece === '') {
                    continue;
                }
                $parsed = parse_jalali_or_gregorian_date($piece);
                $lines[] = $parsed ? (jalali_input($parsed) ?: $piece) : $piece;
            }
            $closed = implode("\n", array_values(array_unique($lines)));
        }
        AppSetting::setValue('daily_log_closed_dates', $closed);
    }
}
