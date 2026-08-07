<?php

if (! function_exists('toman')) {
    function toman(?int $amount): string
    {
        return number_format((int) $amount).' تومان';
    }
}

if (! function_exists('jalali_convert')) {
    /**
     * Gregorian Y-m-d → [jy, jm, jd]
     *
     * @return array{0:int,1:int,2:int}
     */
    function jalali_convert(int $gy, int $gm, int $gd): array
    {
        $g_d_m = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];
        $gy2 = $gm > 2 ? $gy + 1 : $gy;
        $days = 355666 + (365 * $gy) + intdiv($gy2 + 3, 4) - intdiv($gy2 + 99, 100)
            + intdiv($gy2 + 399, 400) + $gd + $g_d_m[$gm - 1];
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

        return [(int) $jy, (int) $jm, (int) $jd];
    }
}

if (! function_exists('jalali_like')) {
    function jalali_like($date, bool $withTime = true): string
    {
        if ($date instanceof \Illuminate\Support\Optional || blank($date)) {
            return '—';
        }

        try {
            $dt = \Illuminate\Support\Carbon::parse($date)->timezone(config('app.timezone', 'Asia/Tehran'));
            [$jy, $jm, $jd] = jalali_convert((int) $dt->format('Y'), (int) $dt->format('n'), (int) $dt->format('j'));
            $base = sprintf('%04d/%02d/%02d', $jy, $jm, $jd);

            return $withTime ? $base.' '.$dt->format('H:i') : $base;
        } catch (\Throwable) {
            return '—';
        }
    }
}

if (! function_exists('jalali_date')) {
    function jalali_date($date): string
    {
        return jalali_like($date, false);
    }
}
