<?php

namespace Plugins\BizCard\src;

use App\Support\JsonSettings;

class CardConfig
{
    public const KEY = 'biz_card_settings';

    /** @return array<string,mixed> */
    public static function defaults(): array
    {
        return [
            'enabled' => true,
            'brand' => 'سرزمین هارد',
            'kicker' => 'HDD Land · فرازنت آمل',
            'title' => 'سرزمین هارد',
            'subtitle' => 'تأمین هارد، SSD و خدمات سازمانی با گارانتی شفاف',
            'logo' => '/images/card/icon.png',
            'hero' => '/images/card/hero.jpg',
            'phone' => '01144447220',
            'email' => '',
            'address' => 'مازندران، آمل، خیابان هراز، بلوار طبری، ساختمان کچپی، طبقه ۲، واحد ۱۰۶',
            'map_url' => 'https://maps.google.com/?q='.rawurlencode('آمل بلوار طبری ساختمان کچپی واحد ۱۰۶'),
            'website' => 'https://hdd-land.ir',
            'wa_sales' => '09123486391',
            'wa_sales_label' => 'واتساپ فروش',
            'wa_support' => '09113611062',
            'wa_support_label' => 'واتساپ پشتیبانی',
            'telegram' => 'peter_sany',
            'instagram' => 'peter.sany',
            'show_save' => true,
            'show_call' => true,
            'show_social' => true,
            'show_links' => true,
            'show_club' => true,
            'show_map' => true,
            'accent' => '#e23d12',
            'club_title' => 'باشگاه مشتری سرزمین هارد',
            'club_text' => 'شماره موبایل خود را وارد کنید. سایت یک پیامک برگشت می‌فرستد تا ثبت‌نام را تأیید کنید.',
            'club_button' => 'درخواست عضویت باشگاه مشتری',
            'club_success' => 'درخواست ثبت شد. پیامک تأیید به‌زودی برایتان ارسال می‌شود.',
            'club_confirm_ok' => 'عضویت شما در باشگاه مشتری سرزمین هارد تأیید شد.',
            'sms_enabled' => true,
            'sms_provider' => 'auto',
            'sms_api_key' => '',
            'sms_api_secret' => '',
            'sms_username' => '',
            'sms_password' => '',
            'sms_sender' => '',
            'sms_tpl_link' => 'سرزمین هارد: کارت ویزیت دیجیتال ما را از این لینک باز کنید: {link}',
            'sms_tpl_club' => 'سرزمین هارد: درخواست عضویت باشگاه مشتری ثبت شد. برای تأیید ثبت‌نام این لینک را باز کنید: {link}',
            'sms_tpl_confirm' => 'سرزمین هارد: عضویت شما در باشگاه مشتری تأیید شد. کارت ویزیت: {card}',
            'contact_section' => 'ارتباط مستقیم — از صفحه تماس فعلی',
            'links_section' => 'منوها و گزینه‌های سایت که به کارت وصل می‌شود',
            'footer_note' => 'قدرت گرفته از سایت سرزمین هارد',
            'vcard_filename' => 'sarzamin-hard.vcf',
            'links' => implode("\n", [
                'contact|واتساپ فروش|۰۹۱۲ ۳۴۸ ۶۳۹۱|wa:sales|گفتگو',
                'contact|واتساپ پشتیبانی|۰۹۱۱ ۳۶۱ ۱۰۶۲|wa:support|گفتگو',
                'contact|تلگرام|@peter_sany|tg:peter_sany|باز کردن',
                'contact|اینستاگرام|peter.sany|ig:peter.sany|مشاهده',
                'site|ورود به فروشگاه|/products و وب‌اپ /app|/products|فروشگاه',
                'site|استعلام و ثبت گارانتی|منوی گارانتی فعلی|/serial-check|گارانتی',
                'site|پیگیری قبض|support.hdd-land.ir|https://support.hdd-land.ir|قبض',
                'site|تیکت پشتیبانی|کارتابل حساب مشتری|/account/tickets|تیکت',
                'site|خدمات سازمانی|تأمین هارد / CCTV / شعب|/enterprise-storage|سازمانی',
                'site|آکادمی و آموزش|بازیابی، تعمیر HDD، NVMe، RAID|/training|آموزش',
                'site|طراحی و فروش سایت|تعمیرکاران / فروشگاهی / شرکتی|/sites/repair-shop|سایت',
                'site|دفتر آمل|هراز، طبری، ساختمان کچپی، واحد ۱۰۶|map:office|نقشه',
            ]),
        ];
    }

    /** @return array<string,mixed> */
    public static function get(): array
    {
        return JsonSettings::get(self::KEY, static::defaults());
    }

    public static function isEnabled(): bool
    {
        return ! empty(static::get()['enabled']);
    }

    /** @param  array<string,mixed>  $data */
    public static function save(array $data): array
    {
        $label = static fn ($v, int $max, string $fallback = '') => (mb_substr(trim((string) $v), 0, $max) ?: $fallback);
        $color = static fn ($v, string $fallback) => preg_match('/^#[0-9a-fA-F]{6}$/', (string) $v) ? (string) $v : $fallback;

        return JsonSettings::save(self::KEY, static::defaults(), $data, [
            'enabled', 'show_save', 'show_call', 'show_social', 'show_links', 'show_club', 'show_map', 'sms_enabled',
        ], [
            'brand' => fn ($v) => $label($v, 80, 'سرزمین هارد'),
            'kicker' => fn ($v) => $label($v, 80),
            'title' => fn ($v) => $label($v, 80, 'سرزمین هارد'),
            'subtitle' => fn ($v) => $label($v, 240),
            'logo' => fn ($v) => $label($v, 500, '/images/card/icon.png'),
            'hero' => fn ($v) => $label($v, 500, '/images/card/hero.jpg'),
            'phone' => fn ($v) => preg_replace('/[^\d+]/', '', (string) $v) ?: '01144447220',
            'email' => fn ($v) => $label($v, 190),
            'address' => fn ($v) => $label($v, 300),
            'map_url' => fn ($v) => $label($v, 500),
            'website' => fn ($v) => $label($v, 200, 'https://hdd-land.ir'),
            'wa_sales' => fn ($v) => preg_replace('/[^\d+]/', '', (string) $v),
            'wa_sales_label' => fn ($v) => $label($v, 60, 'واتساپ فروش'),
            'wa_support' => fn ($v) => preg_replace('/[^\d+]/', '', (string) $v),
            'wa_support_label' => fn ($v) => $label($v, 60, 'واتساپ پشتیبانی'),
            'telegram' => fn ($v) => ltrim((string) $v, '@'),
            'instagram' => fn ($v) => ltrim((string) $v, '@'),
            'accent' => fn ($v) => $color($v, '#e23d12'),
            'club_title' => fn ($v) => $label($v, 80, 'باشگاه مشتری سرزمین هارد'),
            'club_text' => fn ($v) => $label($v, 400),
            'club_button' => fn ($v) => $label($v, 60, 'درخواست عضویت باشگاه مشتری'),
            'club_success' => fn ($v) => $label($v, 240),
            'club_confirm_ok' => fn ($v) => $label($v, 240),
            'sms_provider' => fn ($v) => in_array((string) $v, ['auto', 'auth', 'kavenegar', 'ippanel', 'melipayamak'], true) ? (string) $v : 'auto',
            'sms_api_key' => fn ($v) => mb_substr(trim((string) $v), 0, 200),
            'sms_api_secret' => fn ($v) => mb_substr(trim((string) $v), 0, 200),
            'sms_username' => fn ($v) => mb_substr(trim((string) $v), 0, 80),
            'sms_password' => function ($v) {
                $v = trim((string) $v);

                return $v === '' ? (string) (static::get()['sms_password'] ?? '') : mb_substr($v, 0, 120);
            },
            'sms_sender' => fn ($v) => mb_substr(trim((string) $v), 0, 40),
            'sms_tpl_link' => fn ($v) => $label($v, 500),
            'sms_tpl_club' => fn ($v) => $label($v, 500),
            'sms_tpl_confirm' => fn ($v) => $label($v, 500),
            'contact_section' => fn ($v) => $label($v, 120),
            'links_section' => fn ($v) => $label($v, 120),
            'footer_note' => fn ($v) => $label($v, 160),
            'vcard_filename' => fn ($v) => preg_replace('/[^a-zA-Z0-9._-]/', '', (string) $v) ?: 'sarzamin-hard.vcf',
            'links' => fn ($v) => mb_substr(trim((string) $v), 0, 8000),
        ]);
    }

    /**
     * @param  array<string,mixed>|null  $s
     * @return list<array{group:string,label:string,hint:string,url:string,action:string}>
     */
    public static function links(?array $s = null): array
    {
        $s = $s ?? static::get();
        $out = [];
        foreach (preg_split('/\R/u', (string) ($s['links'] ?? '')) ?: [] as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            [$group, $label, $hint, $url, $action] = array_pad(explode('|', $line, 5), 5, '');
            $label = trim($label);
            if ($label === '') {
                continue;
            }
            $out[] = [
                'group' => trim($group) ?: 'site',
                'label' => mb_substr($label, 0, 80),
                'hint' => mb_substr(trim($hint), 0, 120),
                'url' => static::resolveUrl(trim($url), $s),
                'action' => mb_substr(trim($action) ?: 'باز کردن', 0, 30),
            ];
        }

        return $out;
    }

    /** @param  array<string,mixed>  $s */
    public static function resolveUrl(string $url, array $s): string
    {
        if ($url === '' || $url === '#') {
            return '#';
        }
        if ($url === 'wa:sales') {
            return static::whatsappUrl((string) ($s['wa_sales'] ?? ''));
        }
        if ($url === 'wa:support') {
            return static::whatsappUrl((string) ($s['wa_support'] ?? ''));
        }
        if (str_starts_with($url, 'tg:')) {
            $u = ltrim(substr($url, 3), '@');

            return $u !== '' ? 'https://t.me/'.$u : 'https://t.me/'.ltrim((string) ($s['telegram'] ?? ''), '@');
        }
        if (str_starts_with($url, 'ig:')) {
            $u = ltrim(substr($url, 3), '@');

            return $u !== '' ? 'https://instagram.com/'.$u : 'https://instagram.com/'.ltrim((string) ($s['instagram'] ?? ''), '@');
        }
        if ($url === 'map:office') {
            $map = trim((string) ($s['map_url'] ?? ''));

            return $map !== '' ? $map : 'https://maps.google.com/?q='.rawurlencode((string) ($s['address'] ?? ''));
        }

        return $url;
    }

    public static function whatsappUrl(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if ($digits === '') {
            return '#';
        }
        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }
        if (str_starts_with($digits, '0')) {
            $digits = '98'.substr($digits, 1);
        }
        if (! str_starts_with($digits, '98')) {
            $digits = '98'.$digits;
        }

        return 'https://wa.me/'.$digits;
    }

    public static function telHref(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        return $digits !== '' ? 'tel:'.$digits : '#';
    }

    public static function prettyPhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if (preg_match('/^(\d{3})(\d{4})(\d{4})$/', $digits, $m)) {
            return $m[1].' '.$m[2].' '.$m[3];
        }
        if (preg_match('/^(\d{4})(\d{3})(\d{4})$/', $digits, $m)) {
            return $m[1].' '.$m[2].' '.$m[3];
        }
        if (preg_match('/^(09\d{2})(\d{3})(\d{4})$/', $digits, $m)) {
            return $m[1].' '.$m[2].' '.$m[3];
        }

        return $phone;
    }

    public static function assetUrl(string $path): string
    {
        $path = trim($path);
        if ($path === '') {
            return '';
        }
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return function_exists('asset') ? asset(ltrim($path, '/')) : '/'.ltrim($path, '/');
    }

    public static function publicCardUrl(): string
    {
        return function_exists('url') ? url('/card') : '/card';
    }

    public static function vcardUrl(): string
    {
        return function_exists('url') ? url('/card/vcard') : '/card/vcard';
    }

    /** @param  array<string,mixed>|null  $s */
    public static function buildVcard(?array $s = null): string
    {
        $s = $s ?? static::get();
        $esc = static function (string $v): string {
            $v = str_replace(["\r\n", "\n", "\r"], '\\n', $v);

            return str_replace([',', ';'], ['\\,', '\\;'], $v);
        };

        $lines = [
            'BEGIN:VCARD',
            'VERSION:3.0',
            'FN;CHARSET=UTF-8:'.$esc((string) ($s['title'] ?? $s['brand'] ?? 'سرزمین هارد')),
            'N;CHARSET=UTF-8:'.$esc((string) ($s['brand'] ?? 'سرزمین هارد')).';;;;',
            'ORG;CHARSET=UTF-8:'.$esc((string) ($s['kicker'] ?? 'HDD Land')),
            'TITLE;CHARSET=UTF-8:'.$esc((string) ($s['subtitle'] ?? '')),
        ];
        $phone = preg_replace('/\D+/', '', (string) ($s['phone'] ?? '')) ?? '';
        if ($phone !== '') {
            $lines[] = 'TEL;TYPE=WORK,VOICE:'.$phone;
        }
        $sales = preg_replace('/\D+/', '', (string) ($s['wa_sales'] ?? '')) ?? '';
        if ($sales !== '') {
            $lines[] = 'TEL;TYPE=CELL:'.$sales;
        }
        $email = trim((string) ($s['email'] ?? ''));
        if ($email !== '') {
            $lines[] = 'EMAIL;TYPE=WORK:'.$email;
        }
        $address = trim((string) ($s['address'] ?? ''));
        if ($address !== '') {
            $lines[] = 'ADR;CHARSET=UTF-8;TYPE=WORK:;;'.$esc($address).';;;;';
        }
        $web = trim((string) ($s['website'] ?? ''));
        if ($web !== '') {
            $lines[] = 'URL:'.$web;
        }
        $lines[] = 'URL:'.static::publicCardUrl();
        $tg = ltrim((string) ($s['telegram'] ?? ''), '@');
        if ($tg !== '') {
            $lines[] = 'X-SOCIALPROFILE;TYPE=telegram:https://t.me/'.$tg;
        }
        $ig = ltrim((string) ($s['instagram'] ?? ''), '@');
        if ($ig !== '') {
            $lines[] = 'X-SOCIALPROFILE;TYPE=instagram:https://instagram.com/'.$ig;
        }
        $lines[] = 'NOTE;CHARSET=UTF-8:'.$esc((string) ($s['subtitle'] ?? ''));
        $lines[] = 'END:VCARD';

        return implode("\r\n", $lines)."\r\n";
    }
}
