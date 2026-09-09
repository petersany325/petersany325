<?php

namespace App\Support;

use App\Models\Setting;

class FooterConfig
{
    public const KEY = 'modern_footer_settings';

    public static function defaults(): array
    {
        return [
            'enabled' => true,
            'brand' => 'سرزمین هارد',
            'description' => 'تأمین تخصصی هارد، SSD و تجهیزات ذخیره‌سازی برای فروشگاه و سازمان با گارانتی شفاف.',
            'phone' => '01144447220',
            'email' => '',
            'address' => 'مازندران، آمل، خیابان هراز، بلوار طبری، ساختمان کچپی، طبقه ۲، واحد ۱۰۶',
            'bg' => '#0b1220',
            'accent' => '#e23d12',
            'text' => '#f8fafc',
            'muted' => '#94a3b8',
            'column1_title' => 'فروشگاه',
            'column1_links' => "محصولات|/products\nپیگیری سفارش|/orders/track\nاستعلام گارانتی|/serial-check",
            'column2_title' => 'خدمات مشتریان',
            'column2_links' => "حساب کاربری|/account\nپشتیبانی|/account/tickets\nتماس با ما|/contact\nدرباره ما|/about",
            'social_links' => "اینستاگرام|#\nتلگرام|#\nواتساپ|#",
            'trust_items' => "موجودی واقعی\nگارانتی شفاف\nپشتیبانی سریع",
            'show_newsletter' => true,
            'newsletter_title' => 'از قیمت و موجودی جدید باخبر شوید',
            'newsletter_text' => 'ایمیل‌تان را ثبت کنید تا پیشنهادهای مهم را از دست ندهید.',
            'copyright' => 'سرزمین هارد — همه حقوق محفوظ است.',
            'show_webapp' => true,
            'show_back_top' => true,
        ];
    }

    public static function get(): array
    {
        $raw = Setting::getValue(self::KEY, []);
        if (is_string($raw)) {
            $raw = json_decode($raw, true) ?: [];
        }

        return array_merge(self::defaults(), is_array($raw) ? $raw : []);
    }

    public static function save(array $d): void
    {
        $s = self::get();
        foreach (['enabled', 'show_newsletter', 'show_webapp', 'show_back_top'] as $k) {
            $s[$k] = ! empty($d[$k]);
        }
        foreach ([
            'brand' => 80,
            'description' => 500,
            'phone' => 40,
            'email' => 190,
            'address' => 300,
            'column1_title' => 80,
            'column2_title' => 80,
            'column1_links' => 3000,
            'column2_links' => 3000,
            'social_links' => 2000,
            'trust_items' => 1000,
            'newsletter_title' => 150,
            'newsletter_text' => 350,
            'copyright' => 250,
        ] as $k => $max) {
            $s[$k] = mb_substr(trim((string) ($d[$k] ?? '')), 0, $max);
        }
        foreach (['bg' => '#0b1220', 'accent' => '#e23d12', 'text' => '#f8fafc', 'muted' => '#94a3b8'] as $k => $fallback) {
            $s[$k] = preg_match('/^#[0-9a-fA-F]{6}$/', (string) ($d[$k] ?? '')) ? $d[$k] : $fallback;
        }
        Setting::setValue(self::KEY, $s);
    }

    public static function links(string $raw): array
    {
        $out = [];
        foreach (preg_split('/\R/u', $raw) ?: [] as $line) {
            [$label, $url] = array_pad(explode('|', $line, 2), 2, '');
            $label = trim($label);
            $url = trim($url);
            if ($label !== '' && $url !== '') {
                $out[] = ['label' => $label, 'url' => $url];
            }
        }

        return $out;
    }
}
