<?php

namespace Plugins\WebApp\src\Support;

use App\Models\Setting;
use App\Support\FooterConfig;
use App\Support\PortalNav;

/**
 * Live sync from MegaMenu / ThemeBuilder / shop settings / FooterConfig → WebApp.
 * When admin updates site menus/theme/footer, WebApp picks them up automatically.
 */
class SiteSync
{
    /** @return array{image:string,title:string,text:string,cta_label:string,cta_url:string}|null */
    public static function heroBanner(): ?array
    {
        try {
            if (! class_exists(\Plugins\ThemeBuilder\src\ThemeConfig::class)) {
                return null;
            }
            $theme = \Plugins\ThemeBuilder\src\ThemeConfig::get();
            $banner = $theme['banner'] ?? [];
            if (empty($banner['enabled'])) {
                return null;
            }
            $layers = collect($banner['layers'] ?? [])->keyBy('id');
            $read = static function ($layer, string $fallback = ''): string {
                return $layer && ! empty($layer['enabled']) && empty($layer['deleted'])
                    ? trim((string) ($layer['content'] ?? $fallback)) : $fallback;
            };
            $title = $read($layers->get('title'), (string) ($banner['overlay_title'] ?? ''));
            $text = $read($layers->get('text'), (string) ($banner['overlay_text'] ?? ''));
            $cta = $layers->get('cta1');

            return [
                'image' => \Plugins\ThemeBuilder\src\ThemeConfig::bannerUrl($banner, 1),
                'title' => $title,
                'text' => $text,
                'cta_label' => $read($cta, (string) ($banner['cta_text'] ?? 'مشاهده محصولات')),
                'cta_url' => PortalNav::mapUrlForWebApp(trim((string) ($cta['url'] ?? $banner['cta_url'] ?? '/app/shop')) ?: '/app/shop'),
            ];
        } catch (\Throwable) {
            return null;
        }
    }

    /** @return list<array{label:string,url:string,children:list<array{label:string,url:string}>}> */
    public static function menuItems(int $limit = 12): array
    {
        $out = [];
        try {
            if (! class_exists(\Plugins\MegaMenu\src\Models\MegaMenuItem::class)) {
                return $out;
            }
            if (class_exists(\Plugins\MegaMenu\Plugin::class)) {
                \Plugins\MegaMenu\Plugin::ensureSchema();
            }
            $tree = \Plugins\MegaMenu\src\Models\MegaMenuItem::tree();
            foreach ($tree as $item) {
                if (count($out) >= $limit) {
                    break;
                }
                $label = trim((string) ($item->title ?? ''));
                if ($label === '' || PortalNav::isPlaceholderLabel($label)) {
                    continue;
                }
                $rawUrl = method_exists($item, 'href') ? (string) $item->href() : (string) ($item->url ?? '');
                // Prefer relative path for mapping
                $pathUrl = (string) ($item->url ?? '');
                if ($pathUrl === '' || PortalNav::isDeadUrl($pathUrl)) {
                    $pathUrl = $rawUrl;
                }
                $url = PortalNav::mapUrlForWebApp($pathUrl);
                if (PortalNav::isDeadUrl($url)) {
                    continue;
                }

                $children = [];
                $kids = $item->activeChildren ?? collect();
                foreach ($kids as $child) {
                    $cl = trim((string) ($child->title ?? ''));
                    if ($cl === '' || PortalNav::isPlaceholderLabel($cl)) {
                        continue;
                    }
                    $cPath = (string) ($child->url ?? '');
                    if ($cPath === '' || PortalNav::isDeadUrl($cPath)) {
                        $cPath = method_exists($child, 'href') ? (string) $child->href() : '';
                    }
                    if (PortalNav::isDeadUrl($cPath)) {
                        continue;
                    }
                    $children[] = [
                        'label' => $cl,
                        'url' => PortalNav::mapUrlForWebApp($cPath),
                    ];
                }

                // Parent with only dead children and dead/home-ish junk → skip unless useful url
                if ($children === [] && in_array($url, ['/app', '/'], true) && $label !== 'خانه') {
                    // Try blueprint match by label
                    $fallback = static::blueprintMatch($label);
                    if ($fallback) {
                        $url = $fallback['url'];
                        $children = $fallback['children'] ?? [];
                    }
                }

                $out[] = [
                    'label' => static::normalizeLabel($label),
                    'url' => $url !== '' ? $url : '/app',
                    'children' => $children,
                ];
            }
        } catch (\Throwable) {
            //
        }

        return $out;
    }

    /** @return list<array{label:string,url:string}> */
    public static function quickLinks(int $limit = 6): array
    {
        $out = [];
        try {
            if (class_exists(\Plugins\ThemeBuilder\src\ThemeConfig::class)) {
                $theme = \Plugins\ThemeBuilder\src\ThemeConfig::get();
                $tm = $theme['top_menu'] ?? [];
                if (! empty($tm['enabled']) && ! empty($tm['items']) && is_array($tm['items'])) {
                    foreach ($tm['items'] as $item) {
                        if (count($out) >= $limit) {
                            break;
                        }
                        $label = trim((string) ($item['label'] ?? ''));
                        $url = trim((string) ($item['url'] ?? ''));
                        if ($label === '' || PortalNav::isDeadUrl($url) || PortalNav::isPlaceholderLabel($label)) {
                            continue;
                        }
                        $out[] = [
                            'label' => static::normalizeLabel($label),
                            'url' => PortalNav::mapUrlForWebApp($url),
                        ];
                    }
                }
            }
        } catch (\Throwable) {
            //
        }

        if ($out !== []) {
            return $out;
        }

        foreach (static::menuItems($limit) as $m) {
            if (PortalNav::isDeadUrl($m['url'])) {
                continue;
            }
            $out[] = ['label' => $m['label'], 'url' => $m['url']];
            if (count($out) >= $limit) {
                break;
            }
        }

        if ($out === []) {
            foreach (PortalNav::webappSiteMenu() as $m) {
                $out[] = ['label' => $m['label'], 'url' => $m['url']];
                if (count($out) >= $limit) {
                    break;
                }
            }
        }

        return $out;
    }

    public static function shopName(string $fallback = 'سرزمین هارد'): string
    {
        try {
            $n = trim((string) Setting::getValue('shop_name', $fallback));

            return $n !== '' ? $n : $fallback;
        } catch (\Throwable) {
            return $fallback;
        }
    }

    public static function brandColor(string $fallback = '#e23d12'): string
    {
        try {
            if (class_exists(\Plugins\MegaMenu\Plugin::class)) {
                $mm = \Plugins\MegaMenu\Plugin::settings();
                $c = (string) ($mm['accent'] ?? $mm['accent_color'] ?? $mm['brand_color'] ?? '');
                if (preg_match('/^#[0-9a-fA-F]{6}$/', $c)) {
                    return $c;
                }
            }
        } catch (\Throwable) {
            //
        }

        return $fallback;
    }

    /** @param  array<string,mixed>  $s  WebApp settings */
    public static function resolveBrand(array $s): array
    {
        $name = (string) ($s['app_name'] ?? 'سرزمین هارد');
        $color = (string) ($s['theme_color'] ?? '#e23d12');
        if (! empty($s['sync_brand_from_site'])) {
            $name = static::shopName($name);
            $color = static::brandColor($color);
        }

        return ['app_name' => $name, 'theme_color' => $color];
    }

    /** @param  array<string,mixed>  $s */
    public static function resolveQuickLinks(array $s): array
    {
        if (! empty($s['sync_quick_links_from_theme'])) {
            $live = static::quickLinks(6);
            if ($live !== []) {
                return $live;
            }
        }

        $manual = [];
        foreach ([1, 2, 3] as $i) {
            $lab = trim((string) ($s['quick_link_'.$i.'_label'] ?? ''));
            $href = trim((string) ($s['quick_link_'.$i.'_url'] ?? ''));
            if ($lab !== '' && ! PortalNav::isDeadUrl($href)) {
                $manual[] = [
                    'label' => static::normalizeLabel($lab),
                    'url' => PortalNav::mapUrlForWebApp($href),
                ];
            }
        }

        return $manual !== [] ? $manual : array_map(
            fn ($m) => ['label' => $m['label'], 'url' => $m['url']],
            array_slice(PortalNav::webappSiteMenu(), 0, 3)
        );
    }

    /** @param  array<string,mixed>  $s */
    public static function resolveMenu(array $s): array
    {
        if (empty($s['sync_menu_from_site']) && empty($s['show_site_menu'])) {
            return [];
        }

        $limit = (int) ($s['menu_limit'] ?? 12);
        $live = ! empty($s['sync_menu_from_site']) ? static::menuItems($limit) : [];

        // Drop menus that are mostly dead placeholders
        $usable = [];
        foreach ($live as $m) {
            if (! PortalNav::isDeadUrl($m['url']) || ! empty($m['children'])) {
                $usable[] = $m;
            }
        }

        if ($usable === []) {
            return array_slice(PortalNav::webappSiteMenu(), 0, max(4, min(24, $limit)));
        }

        // Dedupe near-identical entries (e.g. گارانتی + استعلام گارانتی → same URL)
        $seen = [];
        $deduped = [];
        foreach ($usable as $m) {
            $key = rtrim((string) $m['url'], '/').'|'.mb_strtolower(preg_replace('/\s+/u', '', $m['label']));
            $urlKey = rtrim((string) $m['url'], '/');
            if (isset($seen[$urlKey]) && (str_contains($m['label'], 'گارانتی') || str_contains($m['label'], 'پیگیری'))) {
                continue;
            }
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $seen[$urlKey] = true;
            $deduped[] = $m;
        }

        return $deduped;
    }

    /**
     * Footer payload for WebApp (from FooterConfig when sync enabled).
     *
     * @param  array<string,mixed>  $s
     * @return array<string,mixed>|null
     */
    public static function resolveFooter(array $s): ?array
    {
        if (empty($s['show_footer'])) {
            return null;
        }

        $ft = [];
        if (! empty($s['sync_footer_from_site']) && class_exists(FooterConfig::class)) {
            try {
                $ft = FooterConfig::get();
            } catch (\Throwable) {
                $ft = [];
            }
        }

        if ($ft === [] || empty($ft['enabled'])) {
            $ft = array_merge(FooterConfig::defaults(), [
                'show_newsletter' => false,
            ]);
        }

        $mapLinks = static function (string $raw) {
            $out = [];
            foreach (FooterConfig::links($raw) as $link) {
                if (PortalNav::isDeadUrl($link['url']) || PortalNav::isPlaceholderLabel($link['label'])) {
                    continue;
                }
                $out[] = [
                    'label' => $link['label'],
                    'url' => PortalNav::mapFooterUrlForWebApp($link['url']),
                ];
            }

            return $out;
        };

        $col1 = $mapLinks((string) ($ft['column1_links'] ?? ''));
        $col2 = $mapLinks((string) ($ft['column2_links'] ?? ''));
        if ($col1 === [] && $col2 === []) {
            foreach (PortalNav::webappSiteMenu() as $m) {
                $col1[] = ['label' => $m['label'], 'url' => $m['url']];
            }
        }

        $phone = (string) ($ft['phone'] ?? '');
        $phoneDigits = preg_replace('/\D+/', '', $phone) ?: '';
        $phoneDisplay = $phoneDigits;
        if (preg_match('/^(\d{3})(\d{4})(\d{4})$/', $phoneDigits, $m)) {
            $phoneDisplay = $m[1].' '.$m[2].' '.$m[3];
        }

        return [
            'brand' => (string) ($ft['brand'] ?? static::shopName('سرزمین هارد')),
            'description' => (string) ($ft['description'] ?? ''),
            'phone' => $phone,
            'phone_digits' => $phoneDigits,
            'phone_display' => $phoneDisplay,
            'email' => (string) ($ft['email'] ?? ''),
            'address' => (string) ($ft['address'] ?? ''),
            'copyright' => (string) ($ft['copyright'] ?? 'سرزمین هارد — همه حقوق محفوظ است.'),
            'bg' => (string) ($ft['bg'] ?? '#0b1220'),
            'accent' => (string) ($ft['accent'] ?? '#e23d12'),
            'text' => (string) ($ft['text'] ?? '#f8fafc'),
            'muted' => (string) ($ft['muted'] ?? '#94a3b8'),
            'column1_title' => (string) ($ft['column1_title'] ?? 'فروشگاه'),
            'column2_title' => (string) ($ft['column2_title'] ?? 'خدمات'),
            'column1_links' => $col1,
            'column2_links' => $col2,
            'show_site_link' => true,
        ];
    }

    protected static function normalizeLabel(string $label): string
    {
        $label = trim($label);
        $fixes = [
            'پیکیری گارانتی' => 'پیگیری گارانتی',
            'پیگیری ها' => 'پیگیری‌ها',
            'پیگیریها' => 'پیگیری‌ها',
        ];

        return $fixes[$label] ?? $label;
    }

    /** @return array{url:string,children?:list<array{label:string,url:string}>}|null */
    protected static function blueprintMatch(string $label): ?array
    {
        $label = static::normalizeLabel($label);
        foreach (PortalNav::storefront() as $item) {
            if ($item['label'] === $label || str_contains($label, mb_substr($item['label'], 0, 4))) {
                $children = [];
                foreach ($item['children'] ?? [] as $c) {
                    $children[] = [
                        'label' => $c['label'],
                        'url' => PortalNav::mapUrlForWebApp($c['url']),
                    ];
                }

                return [
                    'url' => PortalNav::mapUrlForWebApp($item['url']),
                    'children' => $children,
                ];
            }
        }
        if (str_contains($label, 'گارانتی')) {
            return [
                'url' => '/serial-check',
                'children' => [
                    ['label' => 'استعلام گارانتی', 'url' => '/serial-check'],
                    ['label' => 'سریال‌ها و گارانتی من', 'url' => '/account/serials'],
                    ['label' => 'ثبت گارانتی', 'url' => '/serial-check'],
                ],
            ];
        }
        if (str_contains($label, 'پیگیری')) {
            return ['url' => '/orders/track', 'children' => []];
        }
        if (str_contains($label, 'محصول')) {
            return ['url' => '/app/shop', 'children' => []];
        }

        return null;
    }
}
