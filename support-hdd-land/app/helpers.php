<?php

if (! function_exists('toman')) {
    function toman(?int $amount): string
    {
        return number_format((int) $amount).' تومان';
    }
}

if (! function_exists('jalali_like')) {
    function jalali_like($date): string
    {
        if ($date instanceof \Illuminate\Support\Optional || blank($date)) {
            return '—';
        }

        try {
            return \Illuminate\Support\Carbon::parse($date)
                ->timezone(config('app.timezone'))
                ->format('Y/m/d H:i');
        } catch (\Throwable) {
            return '—';
        }
    }
}
