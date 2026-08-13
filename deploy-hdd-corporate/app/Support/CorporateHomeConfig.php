<?php

namespace App\Support;

use App\Models\Setting;

class CorporateHomeConfig
{
    public const KEY = 'corporate_home_settings';

    public static function defaults(): array
    {
        return [
            // Banner
            'banner_kicker' => 'سایت شرکتی + فروشگاهی',
            'banner_title' => 'بازیابی تخصصی، <em>خرید ابزار</em> در یک خانه',
            'banner_lead' => 'صفحه اول دو دروازه دارد: خدمات شرکتی برای دستگاه آسیب‌دیده، و فروشگاه برای نرم‌افزار و آموزش.',
            'banner_cta_red_label' => 'درخواست بازیابی',
            'banner_cta_red_url' => '/contact',
            'banner_cta_blue_label' => 'ورود به فروشگاه',
            'banner_cta_blue_url' => '/products',
            'banner_gate1_title' => 'مسیر شرکتی',
            'banner_gate1_text' => 'بازیابی · تعمیر · گارانتی',
            'banner_gate2_title' => 'مسیر فروشگاهی',
            'banner_gate2_text' => 'نرم‌افزار · آموزش · لایسنس',
            'banner_orbit_title' => 'DUAL',
            'banner_orbit_subtitle' => 'SERVICE + SHOP',
            'header_cta_label' => 'درخواست بازیابی',
            'header_cta_url' => '/contact',

            // Homepage dual paths
            'paths_heading' => 'دو مسیر اصلی صفحه اول',
            'paths_lead' => 'خدمات و فروشگاه هم‌سطح‌اند؛ جزئیات هرکدام داخل بخش خودش باز می‌شود.',
            'corp_code' => 'CORPORATE',
            'corp_title' => 'خدمات شرکتی',
            'corp_text' => 'بازیابی اطلاعات، تعمیر استوریج و قبول گارانتی هارد — با فرم درخواست جدا.',
            'corp_cta1_label' => 'ثبت درخواست',
            'corp_cta1_url' => '/contact',
            'corp_cta2_label' => 'مشاهده خدمات',
            'corp_cta2_url' => '/services',
            'shop_code' => 'SHOP',
            'shop_title' => 'فروشگاه نرم‌افزار',
            'shop_text' => 'خرید نرم‌افزار بازیابی/تعمیر، پکیج آموزش و لایسنس — با سبد خرید جدا.',
            'shop_cta1_label' => 'باز کردن فروشگاه',
            'shop_cta1_url' => '/products',
            'shop_cta2_label' => 'محصولات',
            'shop_cta2_url' => '/products',

            // Sub paths (title|text|url|label per line)
            'sub_paths' => "گارانتی هارد|قبول گارانتی شرکت‌ها با مدارک و پیگیری.|/warranty|ثبت گارانتی ←\nآموزش تخصصی|دوره تعمیرات هارد و بازیابی اطلاعات.|/training|مشاهده دوره‌ها ←\nبلاگ آموزشی|مقالات و راهنماهای تخصصی برای انتشار محتوا.|/blog|ورود به بلاگ ←",

            // Devices (title|subtitle per line)
            'devices_heading' => 'پوشش دستگاه‌ها',
            'devices_lead' => 'تمرکز شرکتی صفحه اول برای اعتمادسازی.',
            'devices' => "هارد دیسک|منطقی / فیزیکی\nSSD / NVMe|عدم شناسایی\nفلش|USB / کارت\nسرور|RAID / NAS\nDVR|دوربین\nموبایل|داده گوشی\nتعمیر|استوریج\nگارانتی|برندها",

            // CTA band
            'cta_heading' => 'کدام مسیر را لازم دارید؟',
            'cta_lead' => 'خدمت حضوری یا خرید نرم‌افزار — هر دو از صفحه اول شروع می‌شود.',
            'cta_red_label' => 'درخواست بازیابی',
            'cta_red_url' => '/contact',
            'cta_blue_label' => 'ورود به فروشگاه',
            'cta_blue_url' => '/products',

            // Footer
            'footer_tagline' => 'مرکز بازیابی اطلاعات · تعمیر استوریج · فروش نرم‌افزار',
            'footer_about' => 'یک سایت واحد با دو مسیر مشخص: خدمات شرکتی برای دستگاه آسیب‌دیده، فروشگاه برای نرم‌افزار و آموزش.',
            'footer_cta_red_label' => 'درخواست بازیابی',
            'footer_cta_red_url' => '/contact',
            'footer_cta_blue_label' => 'ورود به فروشگاه',
            'footer_cta_blue_url' => '/products',
            'footer_col1_title' => 'خدمات شرکتی',
            'footer_col1_links' => "بازیابی هارد / SSD|/services\nتعمیر استوریج|/services\nتعریف تعمیرات و بازیابی|/services/about-recovery\nگارانتی هارد|/warranty\nمعرفی شرکت|/about",
            'footer_col2_title' => 'فروشگاه',
            'footer_col2_links' => "نرم‌افزار بازیابی|/products\nابزار تعمیرات|/products\nسبد خرید|/cart\nپیگیری سفارش|/orders/track\nتسویه‌حساب|/checkout",
            'footer_col3_title' => 'آموزش و بلاگ',
            'footer_col3_links' => "آموزش تعمیرات هارد|/training\nآموزش بازیابی اطلاعات|/training\nبلاگ آموزشی|/blog\nتماس و پشتیبانی|/contact",
            'footer_contact_title' => 'تماس با ما',
            'footer_hours_title' => 'ساعات پاسخگویی',
            'footer_hours_text' => 'شنبه تا پنجشنبه · ۹ تا ۱۸',
            'footer_copyright' => '— همه حقوق محفوظ است',
            'footer_bottom_links' => "درباره ما|/about\nتماس|/contact\nبلاگ|/blog",
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
        $s = self::defaults();
        $limits = [
            'banner_kicker' => 120,
            'banner_title' => 300,
            'banner_lead' => 600,
            'banner_cta_red_label' => 80,
            'banner_cta_red_url' => 300,
            'banner_cta_blue_label' => 80,
            'banner_cta_blue_url' => 300,
            'banner_gate1_title' => 80,
            'banner_gate1_text' => 160,
            'banner_gate2_title' => 80,
            'banner_gate2_text' => 160,
            'banner_orbit_title' => 40,
            'banner_orbit_subtitle' => 80,
            'header_cta_label' => 80,
            'header_cta_url' => 300,
            'paths_heading' => 160,
            'paths_lead' => 400,
            'corp_code' => 40,
            'corp_title' => 120,
            'corp_text' => 500,
            'corp_cta1_label' => 80,
            'corp_cta1_url' => 300,
            'corp_cta2_label' => 80,
            'corp_cta2_url' => 300,
            'shop_code' => 40,
            'shop_title' => 120,
            'shop_text' => 500,
            'shop_cta1_label' => 80,
            'shop_cta1_url' => 300,
            'shop_cta2_label' => 80,
            'shop_cta2_url' => 300,
            'sub_paths' => 4000,
            'devices_heading' => 160,
            'devices_lead' => 400,
            'devices' => 4000,
            'cta_heading' => 160,
            'cta_lead' => 400,
            'cta_red_label' => 80,
            'cta_red_url' => 300,
            'cta_blue_label' => 80,
            'cta_blue_url' => 300,
            'footer_tagline' => 200,
            'footer_about' => 600,
            'footer_cta_red_label' => 80,
            'footer_cta_red_url' => 300,
            'footer_cta_blue_label' => 80,
            'footer_cta_blue_url' => 300,
            'footer_col1_title' => 80,
            'footer_col1_links' => 4000,
            'footer_col2_title' => 80,
            'footer_col2_links' => 4000,
            'footer_col3_title' => 80,
            'footer_col3_links' => 4000,
            'footer_contact_title' => 80,
            'footer_hours_title' => 80,
            'footer_hours_text' => 160,
            'footer_copyright' => 250,
            'footer_bottom_links' => 2000,
        ];

        foreach ($limits as $k => $max) {
            $val = trim((string) ($d[$k] ?? $s[$k]));
            if ($k === 'banner_title') {
                $val = strip_tags($val, '<em><strong><br><span>');
            }
            $s[$k] = mb_substr($val, 0, $max);
        }

        Setting::setValue(self::KEY, $s);
    }

    /** @return list<array{label:string,url:string}> */
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

    /** @return list<array{title:string,text:string,url:string,label:string}> */
    public static function subPaths(string $raw): array
    {
        $out = [];
        foreach (preg_split('/\R/u', $raw) ?: [] as $line) {
            $parts = array_pad(explode('|', $line, 4), 4, '');
            $title = trim($parts[0]);
            if ($title === '') {
                continue;
            }
            $out[] = [
                'title' => $title,
                'text' => trim($parts[1]),
                'url' => trim($parts[2]) ?: '#',
                'label' => trim($parts[3]) ?: 'مشاهده ←',
            ];
        }

        return $out;
    }

    /** @return list<array{title:string,subtitle:string}> */
    public static function devices(string $raw): array
    {
        $out = [];
        foreach (preg_split('/\R/u', $raw) ?: [] as $line) {
            [$title, $subtitle] = array_pad(explode('|', $line, 2), 2, '');
            $title = trim($title);
            if ($title === '') {
                continue;
            }
            $out[] = ['title' => $title, 'subtitle' => trim($subtitle)];
        }

        return $out;
    }

    public static function href(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '#';
        }
        if (preg_match('#^(https?:)?//#i', $url) || str_starts_with($url, 'tel:') || str_starts_with($url, 'mailto:')) {
            return $url;
        }

        return url('/'.ltrim($url, '/'));
    }
}
