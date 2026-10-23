<?php

namespace App\Support;

class StudioHomeConfig
{
    public const KEY = 'studio_agency_home';

    public const BRAND = 'شرکت فراز نت آمل (سرزمین هارد)';

    public static function defaults(): array
    {
        return [
            'enabled' => true,
            'brand' => self::BRAND,
            'tagline' => 'طراحی سایت، نرم‌افزار و هوش مصنوعی',
            'phone' => '01144447220',
            'show_search' => true,
            'mobile_webapp' => true,
            'search_placeholder' => 'جستجو',
            'nav' => "صفحه اصلی|/\nطراحی سایت|/design\nهوش مصنوعی|/ai\nحسابداری وب|/accounting\nحسابداری کامپیوتر|/computer-accounting\nنمونه کارها|/projects\nتماس|/contact",
            'hero_title' => 'طراحی سایت، نرم‌افزار و هوش مصنوعی',
            'hero_em' => 'هوش مصنوعی',
            'hero_text' => 'شرکت فراز نت آمل سایت حرفه‌ای، نرم‌افزار اختصاصی و هوش مصنوعی می‌سازد — برای کسب‌وکار، فروشگاه و سازمان.',
            'cta1_label' => 'درخواست مشاوره رایگان',
            'cta1_url' => '/contact',
            'cta2_label' => 'مشاهده نمونه کارها',
            'cta2_url' => '/projects',
            'accent' => '#7c5cff',
            'bg' => '#070b18',
            'scene_image' => 'images/studio/studio-hero-scene.jpg',
            'laptop_title' => 'پنل مدیریت',
            'node_1' => 'ایده و طراحی',
            'node_2' => 'سایت مدرن',
            'node_3' => 'نرم‌افزار هوشمند',
            'node_4' => 'هوش مصنوعی',
            'house_image' => '',
            'laptop_image' => '',
            'services_enabled' => true,
            'svc_more_label' => 'مشاهده جزئیات',
            'svc_1_title' => 'طراحی سایت',
            'svc_1_text' => 'وب‌سایت حرفه‌ای، واکنش‌گرا و بهینه‌شده برای دیده شدن برند شما',
            'svc_1_url' => '/design',
            'svc_2_title' => 'ساخت نرم‌افزار',
            'svc_2_text' => 'سامانه اختصاصی، حسابداری، تعمیرگاه و پنل سازمانی — کدنویسی واقعی روی کار شما',
            'svc_2_url' => '/computer-accounting',
            'svc_3_title' => 'هوش مصنوعی',
            'svc_3_text' => 'دستیار آفلاین داخل نرم‌افزار و سایت: تحلیل، گفتگو و پیشنهاد کار بدون ارسال داده به اینترنت',
            'svc_3_url' => '/ai',
            'stats_enabled' => true,
            'stat_1_value' => '۳۵۰+',
            'stat_1_label' => 'پروژه موفق',
            'stat_2_value' => '۹۸٪',
            'stat_2_label' => 'رضایت مشتریان',
            'stat_3_value' => '۱۵+',
            'stat_3_label' => 'سال تجربه تخصصی',
            'stat_4_value' => '۲۵۰+',
            'stat_4_label' => 'مشتری فعال',
            'works_enabled' => true,
            'works_title' => 'نمونه کارها',
            'works_subtitle' => 'برخی از پروژه‌های اخیر ما',
            'works_more_label' => 'مشاهده همه پروژه‌ها',
            'works_more_url' => '/projects',
            'work_1_title' => 'وبسایت فروشگاهی هوشمند',
            'work_1_cat' => 'طراحی سایت',
            'work_1_url' => '/projects',
            'work_1_image' => 'images/studio/studio-work-shop.jpg',
            'work_2_title' => 'پنل سئو و آنالیتیکس',
            'work_2_cat' => 'سئو و پشتیبانی',
            'work_2_url' => '/ai',
            'work_2_image' => 'images/studio/studio-work-seo.jpg',
            'work_3_title' => 'داشبورد نرم‌افزار هوشمند',
            'work_3_cat' => 'نرم‌افزار',
            'work_3_url' => '/accounting',
            'work_3_image' => 'images/studio/studio-work-dash.jpg',
            'work_4_title' => 'وبسایت شرکتی معماری',
            'work_4_cat' => 'طراحی سایت',
            'work_4_url' => '/design',
            'work_4_image' => 'images/studio/studio-work-corp.jpg',
        ];
    }

    /** @return array<string, mixed> */
    public static function get(): array
    {
        $raw = [];
        try {
            $raw = SettingsStore::get(self::KEY, []);
        } catch (\Throwable) {
            $raw = [];
        }
        if (is_string($raw)) {
            $raw = json_decode($raw, true) ?: [];
        }

        $merged = array_merge(self::defaults(), is_array($raw) ? $raw : []);
        $brand = trim((string) ($merged['brand'] ?? ''));
        $legacy = ['', 'سرزمین هارد', 'سرزمین‌هارد', 'HDD Land', 'hdd land', 'Hdd Land'];
        if (in_array($brand, $legacy, true)) {
            $merged['brand'] = self::BRAND;
        }
        $fresh = self::defaults();
        $legacySwap = [
            'tagline' => ['تخصصی طراحی سایت و نرم‌افزار'],
            'hero_title' => ['طراحی سایت و نرم‌افزار'],
            'hero_em' => ['نرم‌افزار'],
            'hero_text' => ['طراحی حرفه‌ای وب‌سایت مدرن و نرم‌افزار هوشمند برای کسب‌وکار، فروشگاه و سازمان — با لایه هوش مصنوعی.'],
            'svc_2_title' => ['نرم‌افزار هوشمند'],
            'svc_2_text' => ['حسابداری، قبض و پنل اختصاصی با لایه هوش مصنوعی'],
            'svc_2_url' => ['/accounting'],
            'svc_3_title' => ['سئو و پشتیبانی'],
            'svc_3_text' => ['بهینه‌سازی برای موتورهای جستجو و پشتیبانی مداوم و حرفه‌ای'],
        ];
        foreach ($legacySwap as $key => $olds) {
            if (in_array(trim((string) ($merged[$key] ?? '')), $olds, true)) {
                $merged[$key] = $fresh[$key];
            }
        }
        $oldNav = "صفحه اصلی|/\nطراحی سایت|/design\nحسابداری وب|/accounting\nحسابداری کامپیوتر|/computer-accounting\nنمونه کارها|/projects\nتماس|/contact";
        if (trim((string) ($merged['nav'] ?? '')) === $oldNav) {
            $merged['nav'] = $fresh['nav'];
        }

        return $merged;
    }

    /** @param  array<string, mixed>  $d */
    public static function save(array $d): array
    {
        $s = self::get();
        foreach (['enabled', 'show_search', 'services_enabled', 'stats_enabled', 'works_enabled', 'mobile_webapp'] as $k) {
            $s[$k] = ! empty($d[$k]);
        }
        foreach ([
            'brand' => 90, 'tagline' => 120, 'phone' => 40, 'search_placeholder' => 80,
            'nav' => 2000,
            'hero_title' => 160, 'hero_em' => 80, 'hero_text' => 500,
            'cta1_label' => 60, 'cta1_url' => 300, 'cta2_label' => 60, 'cta2_url' => 300,
            'laptop_title' => 80,
            'node_1' => 60, 'node_2' => 60, 'node_3' => 60, 'node_4' => 60,
            'scene_image' => 500, 'house_image' => 500, 'laptop_image' => 500,
            'svc_more_label' => 60,
            'svc_1_title' => 80, 'svc_1_text' => 200, 'svc_1_url' => 300,
            'svc_2_title' => 80, 'svc_2_text' => 200, 'svc_2_url' => 300,
            'svc_3_title' => 80, 'svc_3_text' => 200, 'svc_3_url' => 300,
            'stat_1_value' => 20, 'stat_1_label' => 60,
            'stat_2_value' => 20, 'stat_2_label' => 60,
            'stat_3_value' => 20, 'stat_3_label' => 60,
            'stat_4_value' => 20, 'stat_4_label' => 60,
            'works_title' => 80, 'works_subtitle' => 120, 'works_more_label' => 60, 'works_more_url' => 300,
            'work_1_title' => 80, 'work_1_cat' => 60, 'work_1_url' => 300, 'work_1_image' => 500,
            'work_2_title' => 80, 'work_2_cat' => 60, 'work_2_url' => 300, 'work_2_image' => 500,
            'work_3_title' => 80, 'work_3_cat' => 60, 'work_3_url' => 300, 'work_3_image' => 500,
            'work_4_title' => 80, 'work_4_cat' => 60, 'work_4_url' => 300, 'work_4_image' => 500,
        ] as $k => $max) {
            $s[$k] = mb_substr(trim((string) ($d[$k] ?? $s[$k] ?? '')), 0, $max);
        }
        foreach (['accent' => '#7c5cff', 'bg' => '#070b18'] as $k => $fallback) {
            $v = (string) ($d[$k] ?? $s[$k] ?? '');
            $s[$k] = preg_match('/^#[0-9a-fA-F]{6}$/', $v) ? $v : $fallback;
        }
        SettingsStore::set(self::KEY, $s);

        return $s;
    }

    /** @return list<array{label:string,url:string,children?:list<array<string,mixed>>}> */
    public static function navItems(?array $s = null): array
    {
        $items = self::lines(($s ?? self::get())['nav'] ?? '');
        if (class_exists(StudioProductMenus::class)) {
            return StudioProductMenus::attachToNav($items);
        }

        return $items;
    }

    /** @return list<array{title:string,text:string,url:string}> */
    public static function services(?array $s = null): array
    {
        $s = $s ?? self::get();
        $out = [];
        for ($i = 1; $i <= 3; $i++) {
            $title = trim((string) ($s['svc_'.$i.'_title'] ?? ''));
            if ($title === '') {
                continue;
            }
            $out[] = [
                'title' => $title,
                'text' => trim((string) ($s['svc_'.$i.'_text'] ?? '')),
                'url' => trim((string) ($s['svc_'.$i.'_url'] ?? '/')),
            ];
        }

        return $out;
    }

    /** @return list<array{value:string,label:string}> */
    public static function stats(?array $s = null): array
    {
        $s = $s ?? self::get();
        $out = [];
        for ($i = 1; $i <= 4; $i++) {
            $value = trim((string) ($s['stat_'.$i.'_value'] ?? ''));
            if ($value === '') {
                continue;
            }
            $out[] = [
                'value' => $value,
                'label' => trim((string) ($s['stat_'.$i.'_label'] ?? '')),
            ];
        }

        return $out;
    }

    /** @return list<array{title:string,cat:string,url:string,image:string}> */
    public static function works(?array $s = null): array
    {
        $s = $s ?? self::get();
        $out = [];
        for ($i = 1; $i <= 4; $i++) {
            $title = trim((string) ($s['work_'.$i.'_title'] ?? ''));
            if ($title === '') {
                continue;
            }
            $out[] = [
                'title' => $title,
                'cat' => trim((string) ($s['work_'.$i.'_cat'] ?? '')),
                'url' => trim((string) ($s['work_'.$i.'_url'] ?? '/projects')),
                'image' => trim((string) ($s['work_'.$i.'_image'] ?? '')),
            ];
        }

        return $out;
    }

    public static function titleHtml(?array $s = null): string
    {
        $s = $s ?? self::get();
        $title = (string) ($s['hero_title'] ?? '');
        $em = trim((string) ($s['hero_em'] ?? ''));
        if ($em !== '' && mb_strpos($title, $em) !== false) {
            $parts = explode($em, $title, 2);
            $before = trim((string) $parts[0]);
            $after = trim((string) ($parts[1] ?? ''));
            $html = $before !== '' ? e($before).'<br>' : '';

            return $html.'<em>'.e($em).'</em>'.($after !== '' ? e($after) : '');
        }

        return e($title);
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

    public static function mobileWebAppOn(?array $s = null): bool
    {
        $s = $s ?? self::get();

        return ! empty($s['enabled']) && ! empty($s['mobile_webapp']);
    }

    /** @return list<array{label:string,url:string,icon:string}> */
    public static function mobileTabs(?array $s = null): array
    {
        return [
            ['label' => 'خانه', 'url' => '/', 'icon' => '⌂'],
            ['label' => 'طراحی', 'url' => '/design', 'icon' => '✎'],
            ['label' => 'حسابداری', 'url' => '/accounting', 'icon' => '▤'],
            ['label' => 'تعمیرگاه', 'url' => '/repair-shop', 'icon' => '⚒'],
            ['label' => 'تماس', 'url' => '/contact', 'icon' => '☎'],
        ];
    }

    /** @return list<array{label:string,url:string}> */
    protected static function lines(string $raw): array
    {
        $out = [];
        foreach (preg_split('/\R/u', $raw) ?: [] as $line) {
            [$label, $url] = array_pad(explode('|', $line, 2), 2, '');
            $label = trim($label);
            $url = trim($url);
            if ($label !== '') {
                $out[] = ['label' => $label, 'url' => $url !== '' ? $url : '#'];
            }
        }

        return $out;
    }
}
