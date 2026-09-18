<?php

namespace Plugins\ContactDesk\src\Support;

use App\Support\SettingsStore;

class ContactCopy
{
    public const KEY = 'contact_sales_page';

    /** @return array<string, mixed> */
    public static function defaults(): array
    {
        return [
            'kicker' => 'واحد فروش و پشتیبانی',
            'title' => 'تماس با واحد فروش',
            'lead' => 'برای استعلام قیمت، خرید سازمانی، بازیابی و پوشش گارانتی مستقیم با دفتر مرکزی HDD Land در ارتباط باشید. تیکت هم از همین صفحه به کارتابل خودتان می‌رود.',
            'phone_label' => 'تلفن شرکت',
            'phone' => '01144447220',
            'phone_display' => '۰۱۱۴۴۴۴۷۲۲۰',
            'wa1_label' => 'واتساپ فروش',
            'wa1' => '09123486391',
            'wa1_display' => '۰۹۱۲۳۴۸۶۳۹۱',
            'wa2_label' => 'واتساپ پشتیبانی',
            'wa2' => '09113611062',
            'wa2_display' => '۰۹۱۱۳۶۱۱۰۶۲',
            'tg_label' => 'تلگرام',
            'tg' => 'peter_sany',
            'ig_label' => 'اینستاگرام',
            'ig' => 'peter.sany',
            'office_label' => 'دفتر مرکزی',
            'office' => 'مازندران آمل خیابان هراز بلوار طبری روبروی طبری یکم ساختمان کچپی طبقه دوم واحد ۱۰۶',
            'hours_label' => 'ساعت پاسخگویی',
            'hours' => 'شنبه تا پنجشنبه، ساعات اداری',
            'ticket_title' => 'کارتابل تیکت شما',
            'ticket_text' => 'وارد حساب شوید و تیکت را از کارتابل خودتان بفرستید تا پیگیری روی همان نام کاربری بماند.',
            'ticket_cta' => 'ورود به تیکت پشتیبانی',
            'ticket_url' => '/account/tickets',
            'map_label' => 'مسیریابی دفتر',
            'map_url' => 'https://maps.google.com/?q='.rawurlencode('مازندران آمل خیابان هراز بلوار طبری ساختمان کچپی'),
        ];
    }

    /** @return array<string, mixed> */
    public static function get(): array
    {
        $raw = SettingsStore::get(self::KEY, []);
        if (is_string($raw)) {
            $raw = json_decode($raw, true) ?: [];
        }

        return array_merge(self::defaults(), is_array($raw) ? $raw : []);
    }

    public static function save(array $d): void
    {
        $s = self::defaults();
        foreach ([
            'kicker' => 80, 'title' => 120, 'lead' => 500,
            'phone_label' => 60, 'phone' => 40, 'phone_display' => 40,
            'wa1_label' => 60, 'wa1' => 40, 'wa1_display' => 40,
            'wa2_label' => 60, 'wa2' => 40, 'wa2_display' => 40,
            'tg_label' => 40, 'tg' => 80, 'ig_label' => 40, 'ig' => 80,
            'office_label' => 60, 'office' => 300,
            'hours_label' => 60, 'hours' => 160,
            'ticket_title' => 80, 'ticket_text' => 300, 'ticket_cta' => 60, 'ticket_url' => 200,
            'map_label' => 60, 'map_url' => 400,
        ] as $k => $max) {
            $s[$k] = mb_substr(trim((string) ($d[$k] ?? $s[$k])), 0, $max);
        }
        SettingsStore::set(self::KEY, $s);
    }

    public static function digits(string $raw): string
    {
        $map = ['۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9'];
        $raw = strtr($raw, $map);

        return preg_replace('/\D+/', '', $raw) ?: '';
    }

    public static function telHref(string $raw): string
    {
        $d = self::digits($raw);

        return $d !== '' ? 'tel:'.$d : '#';
    }

    public static function waHref(string $raw): string
    {
        $d = self::digits($raw);
        if ($d === '') {
            return '#';
        }
        if (str_starts_with($d, '0')) {
            $d = '98'.substr($d, 1);
        }

        return 'https://wa.me/'.$d;
    }

    public static function tgHref(string $handle): string
    {
        $h = ltrim(trim($handle), '@');

        return $h !== '' ? 'https://t.me/'.$h : '#';
    }

    public static function igHref(string $handle): string
    {
        $h = ltrim(trim($handle), '@');

        return $h !== '' ? 'https://instagram.com/'.$h : '#';
    }
}
