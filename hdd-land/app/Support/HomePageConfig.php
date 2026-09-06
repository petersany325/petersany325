<?php

namespace App\Support;

use App\Models\Setting;

/**
 * Shared homepage / first-screen settings for storefront + WebApp.
 * Replaces hard-coded hero/trust/edu/corp copy that ThemeBuilder banner never controlled.
 */
class HomePageConfig
{
    public const KEY = 'homepage_online_settings';

    public static function defaults(): array
    {
        return [
            'hero_enabled' => true,
            'hero_kicker' => 'شرکت تخصصی ذخیره‌سازی',
            'hero_title' => 'مرکز تخصصی هارد، SSD و تأمین سازمانی',
            'hero_title_em' => 'تأمین سازمانی',
            'hero_text' => 'تأمین تجهیزات ذخیره‌سازی برندهای معتبر با گارانتی شفاف — برای فروشگاه و سازمان.',
            'hero_image' => 'images/home/hero.jpg',
            'hero_cta1_label' => 'ورود به فروشگاه',
            'hero_cta1_url' => '/products',
            'hero_cta2_label' => 'درخواست سازمانی',
            'hero_cta2_url' => '/contact',
            'hero_webapp_cta1_url' => '/app/shop',

            'trust_enabled' => true,
            'trust_1_title' => 'گارانتی شفاف',
            'trust_1_text' => 'استعلام با سریال',
            'trust_2_title' => 'تأمین سازمانی',
            'trust_2_text' => 'پیش‌فاکتور و عمده',
            'trust_3_title' => 'موجودی واقعی',
            'trust_3_text' => 'قابل سفارش',
            'trust_4_title' => 'پشتیبانی ۹ تا ۱۹',
            'trust_4_text' => 'مشاوره تخصصی',

            'search_placeholder' => 'جستجوی محصول، برند، کد…',

            'edu_enabled' => true,
            'edu_title' => 'آموزش‌های هارد و ذخیره‌سازی',
            'edu_subtitle' => 'راهنمای انتخاب، نصب و نگهداری تجهیزات ذخیره‌سازی',
            'edu_more_label' => 'مشاهده همه آموزش‌ها',
            'edu_more_url' => '/blog',
            'edu_1_title' => 'هارد مناسب دوربین و NAS',
            'edu_1_text' => 'تفاوت سری Purple، Red و Gold و اینکه برای کدام کاربری بخرید.',
            'edu_1_image' => 'images/home/edu-hdd.jpg',
            'edu_1_url' => '/blog',
            'edu_2_title' => 'SSD ساتا یا NVMe؟',
            'edu_2_text' => 'مقایسه سرعت، ظرفیت و قیمت برای لپ‌تاپ، دسکتاپ و سرور.',
            'edu_2_image' => 'images/home/edu-ssd.jpg',
            'edu_2_url' => '/blog',
            'edu_3_title' => 'انتخاب NVMe حرفه‌ای',
            'edu_3_text' => 'نکته‌های Gen3 و Gen4، هیت‌سینک و سازگاری اسلات M.2.',
            'edu_3_image' => 'images/home/edu-nvme.jpg',
            'edu_3_url' => '/blog',

            'about_enabled' => true,
            'about_title' => 'معرفی سرزمین هارد',
            'about_text' => "سرزمین هارد تأمین‌کننده تخصصی هارد دیسک، SSD و تجهیزات ذخیره‌سازی است. تمرکز ما روی موجودی واقعی، گارانتی شفاف و مشاوره برای خرید فروشگاهی و سازمانی است.\nاز قطعه تکی تا تجهیز انبار و شعب، مسیر مشخصی برای استعلام، فاکتور و پشتیبانی فنی دارید.",
            'about_image' => 'images/home/about.jpg',
            'about_stat1_title' => 'تأمین تخصصی',
            'about_stat1_text' => 'هارد، SSD، NVMe',
            'about_stat2_title' => 'خرید سازمانی',
            'about_stat2_text' => 'استعلام و پیش‌فاکتور',
            'about_stat3_title' => 'گارانتی شفاف',
            'about_stat3_text' => 'استعلام با سریال',

            'corp_enabled' => true,
            'corp_title' => 'پروژه‌ها و خدمات سازمانی',
            'corp_subtitle' => 'تأمین ذخیره‌سازی برای سازمان، شعب و پروژه‌های نظارتی',
            'corp_1_title' => 'تأمین هارد سازمانی',
            'corp_1_text' => 'موجودی سازمانی، مشاوره مدل و تحویل با فاکتور رسمی.',
            'corp_1_image' => 'images/home/corp-org.jpg',
            'corp_1_url' => '/contact',
            'corp_2_title' => 'تجهیز ذخیره‌سازی شعب',
            'corp_2_text' => 'NAS، هارد سرور و ظرفیت مناسب برای آرشیو و بکاپ.',
            'corp_2_image' => 'images/home/corp-nas.jpg',
            'corp_2_url' => '/contact',
            'corp_3_title' => 'پروژه‌های نظارتی و CCTV',
            'corp_3_text' => 'هارد مناسب دوربین، ظرفیت آرشیو و تأمین عمده.',
            'corp_3_image' => 'images/home/corp-cctv.jpg',
            'corp_3_url' => '/contact',
            'corp_cta_title' => 'خرید سازمانی و استعلام قیمت',
            'corp_cta_text' => 'برای پیش‌فاکتور و تأمین عمده با واحد فروش تماس بگیرید.',
            'corp_cta_label' => 'تماس با واحد فروش',
            'corp_cta_url' => '/contact',

            'webapp_corp_title' => 'پروژه‌ها و خدمات سازمانی',
            'webapp_corp_text' => 'استعلام، پیش‌فاکتور و تأمین عمده برای سازمان، شعب و پروژه‌های نظارتی.',
            'webapp_corp_image' => 'images/home/corp-org.jpg',
            'webapp_corp_cta_label' => 'درخواست سازمانی',
            'webapp_corp_cta_url' => '/contact',

            'brands_enabled' => true,
            'brands_text' => "WD\nSEAGATE\nSAMSUNG\nTOSHIBA\nSANDISK",

            'sync_webapp' => true,
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

    public static function save(array $d): array
    {
        $s = self::get();
        foreach ([
            'hero_enabled', 'trust_enabled', 'edu_enabled', 'about_enabled',
            'corp_enabled', 'brands_enabled', 'sync_webapp',
        ] as $k) {
            $s[$k] = ! empty($d[$k]);
        }

        foreach ([
            'hero_kicker' => 120,
            'hero_title' => 160,
            'hero_title_em' => 80,
            'hero_text' => 500,
            'hero_image' => 500,
            'hero_cta1_label' => 60,
            'hero_cta1_url' => 300,
            'hero_cta2_label' => 60,
            'hero_cta2_url' => 300,
            'hero_webapp_cta1_url' => 300,
            'trust_1_title' => 80, 'trust_1_text' => 120,
            'trust_2_title' => 80, 'trust_2_text' => 120,
            'trust_3_title' => 80, 'trust_3_text' => 120,
            'trust_4_title' => 80, 'trust_4_text' => 120,
            'search_placeholder' => 120,
            'edu_title' => 160, 'edu_subtitle' => 300,
            'edu_more_label' => 80, 'edu_more_url' => 300,
            'edu_1_title' => 160, 'edu_1_text' => 300, 'edu_1_image' => 500, 'edu_1_url' => 300,
            'edu_2_title' => 160, 'edu_2_text' => 300, 'edu_2_image' => 500, 'edu_2_url' => 300,
            'edu_3_title' => 160, 'edu_3_text' => 300, 'edu_3_image' => 500, 'edu_3_url' => 300,
            'about_title' => 160, 'about_text' => 1200, 'about_image' => 500,
            'about_stat1_title' => 80, 'about_stat1_text' => 120,
            'about_stat2_title' => 80, 'about_stat2_text' => 120,
            'about_stat3_title' => 80, 'about_stat3_text' => 120,
            'corp_title' => 160, 'corp_subtitle' => 300,
            'corp_1_title' => 160, 'corp_1_text' => 300, 'corp_1_image' => 500, 'corp_1_url' => 300,
            'corp_2_title' => 160, 'corp_2_text' => 300, 'corp_2_image' => 500, 'corp_2_url' => 300,
            'corp_3_title' => 160, 'corp_3_text' => 300, 'corp_3_image' => 500, 'corp_3_url' => 300,
            'corp_cta_title' => 160, 'corp_cta_text' => 300,
            'corp_cta_label' => 80, 'corp_cta_url' => 300,
            'webapp_corp_title' => 160, 'webapp_corp_text' => 400,
            'webapp_corp_image' => 500, 'webapp_corp_cta_label' => 80, 'webapp_corp_cta_url' => 300,
            'brands_text' => 500,
        ] as $k => $max) {
            $s[$k] = mb_substr(trim((string) ($d[$k] ?? $s[$k] ?? '')), 0, $max);
        }

        Setting::setValue(self::KEY, $s);

        if (! empty($s['sync_webapp'])) {
            self::syncToWebApp($s);
        }

        return $s;
    }

    /** @param  array<string,mixed>  $s */
    public static function syncToWebApp(array $s): void
    {
        try {
            if (! class_exists(\Plugins\WebApp\Plugin::class)) {
                return;
            }
            // Merge into current settings so unrelated toggles are not wiped.
            \Plugins\WebApp\Plugin::saveSettings(array_merge(\Plugins\WebApp\Plugin::settings(), [
                'hero_enabled' => ! empty($s['hero_enabled']),
                'hero_title' => $s['hero_title'] ?? '',
                'hero_text' => $s['hero_text'] ?? '',
                'hero_cta_label' => $s['hero_cta1_label'] ?? '',
                'hero_cta_url' => $s['hero_webapp_cta1_url'] ?? ($s['hero_cta1_url'] ?? '/app/shop'),
            ]));
        } catch (\Throwable) {
            // WebApp may be unavailable in sparse/dev contexts.
        }
    }

    public static function imageUrl(string $path): string
    {
        $path = trim($path);
        if ($path === '') {
            return '';
        }
        if (preg_match('#^(https?:)?//#i', $path) || str_starts_with($path, 'data:')) {
            return $path;
        }

        return asset(ltrim($path, '/'));
    }

    /** @return list<array{title:string,text:string}> */
    public static function trustItems(?array $s = null): array
    {
        $s = $s ?? self::get();
        $out = [];
        for ($i = 1; $i <= 4; $i++) {
            $title = trim((string) ($s['trust_'.$i.'_title'] ?? ''));
            if ($title === '') {
                continue;
            }
            $out[] = [
                'title' => $title,
                'text' => trim((string) ($s['trust_'.$i.'_text'] ?? '')),
            ];
        }

        return $out;
    }

    /** @return list<string> */
    public static function brands(?array $s = null): array
    {
        $s = $s ?? self::get();
        $out = [];
        foreach (preg_split('/\R/u', (string) ($s['brands_text'] ?? '')) ?: [] as $line) {
            $line = trim($line);
            if ($line !== '') {
                $out[] = $line;
            }
        }

        return $out;
    }

    /**
     * Title with optional <em> emphasis for storefront hero.
     */
    public static function heroTitleHtml(?array $s = null): string
    {
        $s = $s ?? self::get();
        $title = (string) ($s['hero_title'] ?? '');
        $em = trim((string) ($s['hero_title_em'] ?? ''));
        if ($em !== '' && mb_strpos($title, $em) !== false) {
            $safeEm = e($em);
            $parts = explode($em, $title, 2);

            return e($parts[0]).'<em>'.$safeEm.'</em>'.e($parts[1] ?? '');
        }

        return e($title);
    }
}
