<?php

namespace App\Support;

use App\Models\Setting;

/**
 * Shared homepage / first-screen settings for storefront + WebApp.
 * Includes visual banner design: size, fonts, colors, drag layout, overlay merge.
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

            // Visual design
            'hero_layout' => 'split-rtl', // split-rtl | split-ltr | overlay | stacked | free
            'hero_height' => 420,
            'hero_radius' => 22,
            'hero_pad_y' => 24,
            'hero_pad_x' => 8,
            'hero_font' => 'Vazirmatn',
            'hero_kicker_size' => 14,
            'hero_title_size' => 34,
            'hero_text_size' => 15,
            'hero_cta_size' => 14,
            'hero_bg' => '#ffffff',
            'hero_kicker_color' => '#e23d12',
            'hero_title_color' => '#0b1220',
            'hero_em_color' => '#e23d12',
            'hero_text_color' => '#475569',
            'hero_cta1_bg' => '#e23d12',
            'hero_cta1_color' => '#ffffff',
            'hero_cta2_bg' => '#ffffff',
            'hero_cta2_color' => '#e23d12',
            'hero_cta2_border' => '#e23d12',
            'hero_overlay_color' => '#0a0f19',
            'hero_overlay_opacity' => 55,
            'hero_image_fit' => 'cover',
            'hero_image_pos' => 'center',
            'hero_media_w' => 48, // % of banner width in split/overlay
            'hero_media_h' => 100,
            'hero_media_x' => 52,
            'hero_media_y' => 0,
            'hero_copy_x' => 4,
            'hero_copy_y' => 18,
            'hero_copy_w' => 46,
            'hero_merge_enabled' => false,
            'hero_merge_image' => '',
            'hero_merge_opacity' => 70,
            'hero_merge_blend' => 'overlay',
            'hero_merge_x' => 58,
            'hero_merge_y' => 12,
            'hero_merge_w' => 36,
            'hero_merge_h' => 70,

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
            'corp_enabled', 'brands_enabled', 'sync_webapp', 'hero_merge_enabled',
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
            'hero_layout' => 40,
            'hero_font' => 60,
            'hero_image_fit' => 20,
            'hero_image_pos' => 20,
            'hero_merge_image' => 500,
            'hero_merge_blend' => 30,
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

        $layouts = ['split-rtl', 'split-ltr', 'overlay', 'stacked', 'free'];
        if (! in_array($s['hero_layout'], $layouts, true)) {
            $s['hero_layout'] = 'split-rtl';
        }
        $fonts = ['Vazirmatn', 'Estedad', 'IRANSansX', 'Dana', 'system-ui'];
        if (! in_array($s['hero_font'], $fonts, true)) {
            $s['hero_font'] = 'Vazirmatn';
        }
        if (! in_array($s['hero_image_fit'], ['cover', 'contain'], true)) {
            $s['hero_image_fit'] = 'cover';
        }
        if (! in_array($s['hero_image_pos'], ['center', 'top', 'bottom', 'left', 'right'], true)) {
            $s['hero_image_pos'] = 'center';
        }
        if (! in_array($s['hero_merge_blend'], ['normal', 'multiply', 'overlay', 'soft-light', 'screen'], true)) {
            $s['hero_merge_blend'] = 'overlay';
        }

        foreach ([
            'hero_height' => [220, 720, 420],
            'hero_radius' => [0, 40, 22],
            'hero_pad_y' => [0, 80, 24],
            'hero_pad_x' => [0, 80, 8],
            'hero_kicker_size' => [10, 28, 14],
            'hero_title_size' => [18, 64, 34],
            'hero_text_size' => [11, 28, 15],
            'hero_cta_size' => [11, 24, 14],
            'hero_overlay_opacity' => [0, 90, 55],
            'hero_media_w' => [20, 80, 48],
            'hero_media_h' => [30, 100, 100],
            'hero_media_x' => [0, 80, 52],
            'hero_media_y' => [0, 70, 0],
            'hero_copy_x' => [0, 80, 4],
            'hero_copy_y' => [0, 70, 18],
            'hero_copy_w' => [20, 80, 46],
            'hero_merge_opacity' => [0, 100, 70],
            'hero_merge_x' => [0, 90, 58],
            'hero_merge_y' => [0, 90, 12],
            'hero_merge_w' => [10, 90, 36],
            'hero_merge_h' => [10, 100, 70],
        ] as $k => [$min, $max, $fallback]) {
            $n = (int) ($d[$k] ?? $s[$k] ?? $fallback);
            $s[$k] = max($min, min($max, $n));
        }

        foreach ([
            'hero_bg', 'hero_kicker_color', 'hero_title_color', 'hero_em_color', 'hero_text_color',
            'hero_cta1_bg', 'hero_cta1_color', 'hero_cta2_bg', 'hero_cta2_color', 'hero_cta2_border',
            'hero_overlay_color',
        ] as $k) {
            $s[$k] = self::color((string) ($d[$k] ?? $s[$k] ?? ''), (string) (self::defaults()[$k] ?? '#000000'));
        }

        Setting::setValue(self::KEY, $s);

        if (! empty($s['sync_webapp'])) {
            self::syncToWebApp($s);
        }

        return $s;
    }

    protected static function color(string $v, string $fallback): string
    {
        $v = trim($v);

        return preg_match('/^#[0-9a-fA-F]{6}$/', $v) ? $v : $fallback;
    }

    /** @param  array<string,mixed>  $s */
    public static function syncToWebApp(array $s): void
    {
        try {
            if (! class_exists(\Plugins\WebApp\Plugin::class)) {
                return;
            }
            \Plugins\WebApp\Plugin::saveSettings(array_merge(\Plugins\WebApp\Plugin::settings(), [
                'hero_enabled' => ! empty($s['hero_enabled']),
                'hero_title' => $s['hero_title'] ?? '',
                'hero_text' => $s['hero_text'] ?? '',
                'hero_cta_label' => $s['hero_cta1_label'] ?? '',
                'hero_cta_url' => $s['hero_webapp_cta1_url'] ?? ($s['hero_cta1_url'] ?? '/app/shop'),
            ]));
        } catch (\Throwable) {
            //
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

    /**
     * Inline CSS custom properties for live banner styling.
     *
     * @param  array<string,mixed>|null  $s
     */
    public static function heroStyleAttr(?array $s = null): string
    {
        $s = $s ?? self::get();
        $font = (string) ($s['hero_font'] ?? 'Vazirmatn');
        $fontStack = $font === 'system-ui'
            ? 'system-ui,-apple-system,Segoe UI,Tahoma,sans-serif'
            : "'{$font}', Vazirmatn, Tahoma, sans-serif";
        $overlay = (string) ($s['hero_overlay_color'] ?? '#0a0f19');
        $op = max(0, min(90, (int) ($s['hero_overlay_opacity'] ?? 55))) / 100;
        $hex = ltrim($overlay, '#');
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        $vars = [
            '--hl-hero-h' => ((int) ($s['hero_height'] ?? 420)).'px',
            '--hl-hero-radius' => ((int) ($s['hero_radius'] ?? 22)).'px',
            '--hl-hero-pad-y' => ((int) ($s['hero_pad_y'] ?? 24)).'px',
            '--hl-hero-pad-x' => ((int) ($s['hero_pad_x'] ?? 8)).'px',
            '--hl-hero-font' => $fontStack,
            '--hl-hero-kicker-size' => ((int) ($s['hero_kicker_size'] ?? 14)).'px',
            '--hl-hero-title-size' => ((int) ($s['hero_title_size'] ?? 34)).'px',
            '--hl-hero-text-size' => ((int) ($s['hero_text_size'] ?? 15)).'px',
            '--hl-hero-cta-size' => ((int) ($s['hero_cta_size'] ?? 14)).'px',
            '--hl-hero-bg' => (string) ($s['hero_bg'] ?? '#ffffff'),
            '--hl-hero-kicker' => (string) ($s['hero_kicker_color'] ?? '#e23d12'),
            '--hl-hero-title' => (string) ($s['hero_title_color'] ?? '#0b1220'),
            '--hl-hero-em' => (string) ($s['hero_em_color'] ?? '#e23d12'),
            '--hl-hero-text' => (string) ($s['hero_text_color'] ?? '#475569'),
            '--hl-hero-cta1-bg' => (string) ($s['hero_cta1_bg'] ?? '#e23d12'),
            '--hl-hero-cta1-color' => (string) ($s['hero_cta1_color'] ?? '#ffffff'),
            '--hl-hero-cta2-bg' => (string) ($s['hero_cta2_bg'] ?? '#ffffff'),
            '--hl-hero-cta2-color' => (string) ($s['hero_cta2_color'] ?? '#e23d12'),
            '--hl-hero-cta2-border' => (string) ($s['hero_cta2_border'] ?? '#e23d12'),
            '--hl-hero-scrim' => "rgba({$r},{$g},{$b},{$op})",
            '--hl-hero-media-w' => ((int) ($s['hero_media_w'] ?? 48)).'%',
            '--hl-hero-media-h' => ((int) ($s['hero_media_h'] ?? 100)).'%',
            '--hl-hero-media-x' => ((int) ($s['hero_media_x'] ?? 52)).'%',
            '--hl-hero-media-y' => ((int) ($s['hero_media_y'] ?? 0)).'%',
            '--hl-hero-copy-x' => ((int) ($s['hero_copy_x'] ?? 4)).'%',
            '--hl-hero-copy-y' => ((int) ($s['hero_copy_y'] ?? 18)).'%',
            '--hl-hero-copy-w' => ((int) ($s['hero_copy_w'] ?? 46)).'%',
            '--hl-hero-img-fit' => (string) ($s['hero_image_fit'] ?? 'cover'),
            '--hl-hero-img-pos' => (string) ($s['hero_image_pos'] ?? 'center'),
            '--hl-hero-merge-op' => (max(0, min(100, (int) ($s['hero_merge_opacity'] ?? 70))) / 100),
            '--hl-hero-merge-blend' => (string) ($s['hero_merge_blend'] ?? 'overlay'),
            '--hl-hero-merge-x' => ((int) ($s['hero_merge_x'] ?? 58)).'%',
            '--hl-hero-merge-y' => ((int) ($s['hero_merge_y'] ?? 12)).'%',
            '--hl-hero-merge-w' => ((int) ($s['hero_merge_w'] ?? 36)).'%',
            '--hl-hero-merge-h' => ((int) ($s['hero_merge_h'] ?? 70)).'%',
        ];

        $out = [];
        foreach ($vars as $k => $v) {
            $out[] = $k.':'.$v;
        }

        return implode(';', $out);
    }
}
