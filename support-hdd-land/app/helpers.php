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

if (! function_exists('app_calendar_type')) {
    function app_calendar_type(): string
    {
        return \App\Support\CalendarSettings::type();
    }
}

if (! function_exists('app_calendar_is_jalali')) {
    function app_calendar_is_jalali(): bool
    {
        return \App\Support\CalendarSettings::isJalali();
    }
}

if (! function_exists('to_persian_digits')) {
    function to_persian_digits(?string $value): string
    {
        return strtr((string) $value, [
            '0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴',
            '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹',
        ]);
    }
}

if (! function_exists('format_app_date')) {
    /**
     * Display date according to admin calendar setting (Jalali default).
     */
    function format_app_date($date, bool $withTime = true): string
    {
        if ($date instanceof \Illuminate\Support\Optional || blank($date)) {
            return '—';
        }

        try {
            $dt = \Illuminate\Support\Carbon::parse($date)->timezone(config('app.timezone', 'Asia/Tehran'));
            if (app_calendar_is_jalali()) {
                [$y, $m, $d] = jalali_convert((int) $dt->format('Y'), (int) $dt->format('n'), (int) $dt->format('j'));
                $base = sprintf('%04d/%02d/%02d', $y, $m, $d);
            } else {
                $base = $dt->format('Y/m/d');
            }
            $out = $withTime ? $base.' '.$dt->format('H:i') : $base;
            if (\App\Support\CalendarSettings::usePersianDigits()) {
                $out = to_persian_digits($out);
            }

            return $out;
        } catch (\Throwable) {
            return '—';
        }
    }
}

if (! function_exists('jalali_like')) {
    function jalali_like($date, bool $withTime = true): string
    {
        return format_app_date($date, $withTime);
    }
}

if (! function_exists('jalali_date')) {
    function jalali_date($date): string
    {
        return format_app_date($date, false);
    }
}

if (! function_exists('jalali_to_gregorian')) {
    /**
     * Jalali Y/M/D → [gy, gm, gd]
     *
     * @return array{0:int,1:int,2:int}
     */
    function jalali_to_gregorian(int $jy, int $jm, int $jd): array
    {
        $jy += 1595;
        $days = -355668 + (365 * $jy) + intdiv($jy, 33) * 8 + intdiv(($jy % 33) + 3, 4)
            + $jd + ($jm < 7 ? ($jm - 1) * 31 : (($jm - 7) * 30) + 186);
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
        $sal_a = [0, 31, (($gy % 4 === 0 && $gy % 100 !== 0) || ($gy % 400 === 0)) ? 29 : 28,
            31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
        $gm = 0;
        for ($gm = 1; $gm <= 12 && $gd > $sal_a[$gm]; $gm++) {
            $gd -= $sal_a[$gm];
        }

        return [(int) $gy, (int) $gm, (int) $gd];
    }
}

if (! function_exists('parse_jalali_or_gregorian_date')) {
    /**
     * Accepts 1404/05/16, 1404-05-16, or 2026-08-07 → Y-m-d Gregorian or null.
     */
    function parse_jalali_or_gregorian_date(?string $input): ?string
    {
        if ($input === null) {
            return null;
        }
        $raw = trim(strtr($input, [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
            '/' => '-', '.' => '-',
        ]));
        if ($raw === '' || ! preg_match('/^(\d{3,4})-(\d{1,2})-(\d{1,2})$/', $raw, $m)) {
            return null;
        }
        $y = (int) $m[1];
        $mo = (int) $m[2];
        $d = (int) $m[3];
        if ($y >= 1200 && $y <= 1599) {
            [$gy, $gm, $gd] = jalali_to_gregorian($y, $mo, $d);
        } elseif ($y >= 1900 && $y <= 2100) {
            $gy = $y;
            $gm = $mo;
            $gd = $d;
        } else {
            return null;
        }
        if (! checkdate($gm, $gd, $gy)) {
            return null;
        }

        return sprintf('%04d-%02d-%02d', $gy, $gm, $gd);
    }
}

if (! function_exists('jalali_input')) {
    /**
     * Format date for calendar text inputs.
     * Always ASCII digits (input-friendly): Jalali 1404/05/16 or Gregorian 2026/08/09.
     */
    function jalali_input($date): string
    {
        if ($date instanceof \Illuminate\Support\Optional || blank($date)) {
            return '';
        }

        try {
            $dt = \Illuminate\Support\Carbon::parse($date)->timezone(config('app.timezone', 'Asia/Tehran'));
            if (app_calendar_is_jalali()) {
                [$y, $m, $d] = jalali_convert((int) $dt->format('Y'), (int) $dt->format('n'), (int) $dt->format('j'));

                return sprintf('%04d/%02d/%02d', $y, $m, $d);
            }

            return $dt->format('Y/m/d');
        } catch (\Throwable) {
            return '';
        }
    }
}

if (! function_exists('merge_jalali_dates')) {
    /**
     * Convert request date fields (Jalali or Gregorian) to Gregorian Y-m-d before validation.
     * Supports dotted nested keys like items.*.warranty_end_date.
     *
     * @param  list<string>  $keys
     */
    function merge_jalali_dates(\Illuminate\Http\Request $request, array $keys): void
    {
        foreach ($keys as $key) {
            if (str_contains($key, '.*')) {
                $parts = explode('.*.', $key, 2);
                if (count($parts) !== 2) {
                    continue;
                }
                [$root, $child] = $parts;
                $rows = $request->input($root);
                if (! is_array($rows)) {
                    continue;
                }
                foreach ($rows as $i => $row) {
                    if (! is_array($row) || ! array_key_exists($child, $row)) {
                        continue;
                    }
                    $raw = $row[$child];
                    if ($raw === null || $raw === '') {
                        continue;
                    }
                    $rows[$i][$child] = parse_jalali_or_gregorian_date(is_string($raw) ? $raw : null);
                }
                $request->merge([$root => $rows]);

                continue;
            }

            if (! $request->exists($key)) {
                continue;
            }
            $raw = $request->input($key);
            if ($raw === null || $raw === '') {
                continue;
            }
            if (! is_string($raw)) {
                continue;
            }
            $request->merge([$key => parse_jalali_or_gregorian_date($raw)]);
        }
    }
}

if (! function_exists('jalali_today_parts')) {
    /** @return array{0:int,1:int,2:int} jy,jm,jd */
    function jalali_today_parts(?\DateTimeInterface $date = null): array
    {
        $dt = $date
            ? \Illuminate\Support\Carbon::parse($date)->timezone(config('app.timezone', 'Asia/Tehran'))
            : now(config('app.timezone', 'Asia/Tehran'));

        return jalali_convert((int) $dt->format('Y'), (int) $dt->format('n'), (int) $dt->format('j'));
    }
}

if (! function_exists('jalali_period_range')) {
    /**
     * Iranian calendar periods → [from Y-m-d, to Y-m-d] Gregorian.
     *
     * @return array{0:string,1:string}
     */
    function jalali_period_range(string $period): array
    {
        $tz = config('app.timezone', 'Asia/Tehran');
        $now = now($tz);
        [$jy, $jm, $jd] = jalali_today_parts($now);
        $to = $now->toDateString();

        $toGreg = static function (int $y, int $m, int $d): string {
            [$gy, $gm, $gd] = jalali_to_gregorian($y, $m, $d);

            return sprintf('%04d-%02d-%02d', $gy, $gm, $gd);
        };

        return match ($period) {
            'today' => [$to, $to],
            'this_week' => (static function () use ($now, $to) {
                // هفته ایرانی: شنبه تا جمعه
                $dow = (int) $now->dayOfWeek; // 0=Sun … 6=Sat
                $daysFromSat = ($dow + 1) % 7;
                $from = $now->copy()->subDays($daysFromSat)->toDateString();

                return [$from, $to];
            })(),
            'this_month' => [$toGreg($jy, $jm, 1), $to],
            'last_month' => (static function () use ($jy, $jm, $toGreg) {
                if ($jm <= 1) {
                    $y = $jy - 1;
                    $m = 12;
                } else {
                    $y = $jy;
                    $m = $jm - 1;
                }
                $from = $toGreg($y, $m, 1);
                // last day of that jalali month
                if ($m <= 6) {
                    $last = 31;
                } elseif ($m <= 11) {
                    $last = 30;
                } else {
                    // Esfand: 29 or 30 (jalali leap)
                    $last = 29;
                    for ($d = 30; $d >= 29; $d--) {
                        [$gy, $gm, $gd] = jalali_to_gregorian($y, $m, $d);
                        if (checkdate($gm, $gd, $gy)) {
                            $last = $d;
                            break;
                        }
                    }
                }

                return [$from, $toGreg($y, $m, $last)];
            })(),
            'last_30' => [$now->copy()->subDays(29)->toDateString(), $to],
            'this_year' => [$toGreg($jy, 1, 1), $to],
            default => [$toGreg($jy, $jm, 1), $to],
        };
    }
}

if (! function_exists('resolve_request_date')) {
    /** Parse Jalali/Gregorian request value → Gregorian Y-m-d or fallback. */
    function resolve_request_date(?string $input, ?string $fallback = null): ?string
    {
        if ($input === null || trim($input) === '') {
            return $fallback;
        }
        $parsed = parse_jalali_or_gregorian_date($input);

        return $parsed ?? $fallback;
    }
}

if (! function_exists('jalali_day_labels')) {
    /**
     * Map a list of Gregorian day strings (Y-m-d) to Jalali labels.
     *
     * @param  iterable<int|string, mixed>  $days
     * @return list<string>
     */
    function jalali_day_labels(iterable $days): array
    {
        $out = [];
        foreach ($days as $day) {
            $out[] = jalali_date($day);
        }

        return $out;
    }
}

if (! function_exists('shop_name')) {
    /** Customer brand / company name (white-label). */
    function shop_name(?string $default = null): string
    {
        $fallback = $default ?? (string) config('app.name', 'تعمیرگاه');
        try {
            if (class_exists(\App\Models\AppSetting::class)) {
                $v = \App\Models\AppSetting::getValue('invoice_shop_name');
                if (is_string($v) && trim($v) !== '') {
                    return trim($v);
                }
            }
        } catch (\Throwable) {
        }

        return trim($fallback) !== '' ? trim($fallback) : 'تعمیرگاه';
    }
}

if (! function_exists('shop_tagline')) {
    function shop_tagline(): string
    {
        try {
            if (class_exists(\App\Models\AppSetting::class)) {
                $v = \App\Models\AppSetting::getValue('shop_tagline');
                if (is_string($v) && trim($v) !== '') {
                    return trim($v);
                }
            }
        } catch (\Throwable) {
        }

        return 'سیستم مدیریت تعمیرات';
    }
}

if (! function_exists('shop_office_phone')) {
    /** Main office contact phone shown to customers. */
    function shop_office_phone(): string
    {
        try {
            if (class_exists(\App\Support\RemotePartPreorderSettings::class)) {
                return \App\Support\RemotePartPreorderSettings::officePhone();
            }
        } catch (\Throwable) {
        }

        return '01144447220';
    }
}

if (! function_exists('shop_logo_url')) {
    /**
     * @param  'main'|'header'|'invoice'  $variant
     */
    function shop_logo_url(string $variant = 'main'): string
    {
        $map = [
            'main' => 'images/logo.png',
            'header' => 'images/logo-header.png',
            'invoice' => 'images/logo-invoice.png',
        ];
        $rel = $map[$variant] ?? $map['main'];
        $ver = '1';
        try {
            if (class_exists(\App\Models\AppSetting::class)) {
                $ver = (string) (\App\Models\AppSetting::getValue('brand_logo_version') ?: '1');
                $custom = \App\Models\AppSetting::getValue('brand_logo_'.$variant);
                if (is_string($custom) && trim($custom) !== '') {
                    $rel = ltrim(trim($custom), '/');
                }
            }
        } catch (\Throwable) {
        }

        return asset($rel).'?v='.rawurlencode($ver);
    }
}

if (! function_exists('ascii_digits')) {
    /** Convert Persian/Arabic digits to ASCII 0-9. */
    function ascii_digits(?string $value): string
    {
        $value = (string) $value;
        $map = [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ];

        return strtr($value, $map);
    }
}

if (! function_exists('normalize_receipt_search_query')) {
    /**
     * Normalize staff receipt search: digits-only → T-20N{digits}.
     * Leaves name/phone/serial/SH-… queries unchanged.
     */
    function normalize_receipt_search_query(?string $q): string
    {
        $q = trim(ascii_digits((string) $q));
        if ($q === '' || strcasecmp($q, 'T-20N') === 0) {
            return '';
        }

        if (preg_match('/^t-20n(.*)$/i', $q, $m)) {
            $rest = preg_replace('/\s+/', '', (string) ($m[1] ?? ''));

            return $rest === '' ? '' : 'T-20N'.$rest;
        }

        // Pure numeric suffix typed by staff (e.g. 1000 / 10025).
        if (preg_match('/^\d{3,}$/', $q)) {
            return 'T-20N'.$q;
        }

        return $q;
    }
}
