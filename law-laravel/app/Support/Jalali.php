<?php

namespace App\Support;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use DateTimeInterface;

/**
 * Lightweight Jalali (Shamsi) date helpers — no external package required on host.
 */
class Jalali
{
    public static function format(null|string|DateTimeInterface|CarbonInterface $value, string $format = 'Y/m/d'): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        try {
            $dt = $value instanceof CarbonInterface
                ? $value->copy()
                : Carbon::parse($value);
        } catch (\Throwable) {
            return is_string($value) ? $value : '—';
        }

        [$jy, $jm, $jd] = self::toJalali((int) $dt->year, (int) $dt->month, (int) $dt->day);

        $map = [
            'Y' => sprintf('%04d', $jy),
            'y' => sprintf('%02d', $jy % 100),
            'm' => sprintf('%02d', $jm),
            'n' => (string) $jm,
            'd' => sprintf('%02d', $jd),
            'j' => (string) $jd,
            'H' => $dt->format('H'),
            'i' => $dt->format('i'),
            's' => $dt->format('s'),
            'A' => ((int) $dt->format('H') < 12) ? 'ق.ظ' : 'ب.ظ',
        ];

        $out = '';
        $len = strlen($format);
        for ($i = 0; $i < $len; $i++) {
            $ch = $format[$i];
            $out .= $map[$ch] ?? $ch;
        }

        return $out;
    }

    public static function formatDateTime(null|string|DateTimeInterface|CarbonInterface $value): string
    {
        return self::format($value, 'Y/m/d H:i');
    }

    /**
     * Parse Jalali Y/m/d (or Y-m-d) into Gregorian Y-m-d for DB storage.
     * Also accepts already-Gregorian ISO dates.
     */
    public static function toGregorianDate(?string $input): ?string
    {
        if ($input === null) {
            return null;
        }

        $input = trim(str_replace(['-', '.'], '/', $input));
        $input = self::toEnglishDigits($input);
        if ($input === '') {
            return null;
        }

        // Gregorian ISO date (year >= 1700)
        if (preg_match('/^(\d{4})\/(\d{1,2})\/(\d{1,2})$/', $input, $m)) {
            $y = (int) $m[1];
            $mo = (int) $m[2];
            $d = (int) $m[3];
            if ($y >= 1700) {
                return sprintf('%04d-%02d-%02d', $y, $mo, $d);
            }
            if ($y >= 1200 && $y <= 1600) {
                [$gy, $gm, $gd] = self::toGregorian($y, $mo, $d);

                return sprintf('%04d-%02d-%02d', $gy, $gm, $gd);
            }
        }

        // Already Y-m-d from type=date
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $input, $m) && (int) $m[1] >= 1700) {
            return $input;
        }

        return null;
    }

    /** @return array{0:int,1:int,2:int} */
    public static function toJalali(int $gy, int $gm, int $gd): array
    {
        $g_d_m = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];
        $gy2 = ($gm > 2) ? ($gy + 1) : $gy;
        $days = 355666 + (365 * $gy) + intdiv($gy2 + 3, 4) - intdiv($gy2 + 99, 100) + intdiv($gy2 + 399, 400) + $gd + $g_d_m[$gm - 1];
        $jy = -1595 + (33 * intdiv($days, 12053));
        $days %= 12053;
        $jy += 4 * intdiv($days, 1461);
        $days %= 1461;
        if ($days > 365) {
            $jy += intdiv($days - 1, 365);
            $days = ($days - 1) % 365;
        }
        if ($days < 186) {
            $jm = 1 + intdiv($days, 31);
            $jd = 1 + ($days % 31);
        } else {
            $jm = 7 + intdiv($days - 186, 30);
            $jd = 1 + (($days - 186) % 30);
        }

        return [$jy, $jm, $jd];
    }

    /** @return array{0:int,1:int,2:int} */
    public static function toGregorian(int $jy, int $jm, int $jd): array
    {
        $jy += 1595;
        $days = -355668 + (365 * $jy) + (intdiv($jy, 33) * 8) + intdiv(($jy % 33) + 3, 4) + $jd + (($jm < 7) ? (($jm - 1) * 31) : ((($jm - 7) * 30) + 186));
        $gy = 400 * intdiv($days, 146097);
        $days %= 146097;
        if ($days > 36524) {
            $gy += 100 * intdiv(--$days, 36524);
            $days %= 36524;
            if ($days >= 365) {
                $days++;
            }
        }
        $gy += 4 * intdiv($days, 1461);
        $days %= 1461;
        if ($days > 365) {
            $gy += intdiv($days - 1, 365);
            $days = ($days - 1) % 365;
        }
        $gd = $days + 1;
        $sal_a = [0, 31, (($gy % 4 === 0 && $gy % 100 !== 0) || ($gy % 400 === 0)) ? 29 : 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
        for ($gm = 1; $gm <= 12 && $gd > $sal_a[$gm]; $gm++) {
            $gd -= $sal_a[$gm];
        }

        return [$gy, $gm, $gd];
    }

    public static function toEnglishDigits(string $value): string
    {
        return strtr($value, [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]);
    }
}
