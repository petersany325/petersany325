<?php

namespace Plugins\WebApp;

use App\Support\BasePlugin;
use Plugins\Support\JsonSettings;

class Plugin extends BasePlugin
{
    public const SETTINGS_KEY = 'web_app_settings';

    public function id(): string
    {
        return 'web-app';
    }

    public function name(): string
    {
        return 'وب‌سرویس / وب‌اپ';
    }

    public function description(): string
    {
        return 'وب‌اپ موبایل کامل (PWA): خانه، فروشگاه، محصول، سبد، حساب، جستجو و نصب روی گوشی';
    }

    public function version(): string
    {
        return '2.3.1';
    }

    public function isCore(): bool
    {
        return true;
    }

    /** @return array<string,mixed> */
    public static function defaults(): array
    {
        return [
            'enabled' => true,
            'app_name' => 'سرزمین هارد',
            'short_name' => 'سرزمین‌هارد',
            'description' => 'فروشگاه تخصصی هارد و SSD — وب‌اپ سرزمین هارد',
            'theme_color' => '#e23d12',
            'background_color' => '#1a1d23',
            'surface_color' => '#ffffff',
            'text_color' => '#1a1d23',
            'start_url' => '/app',
            'display' => 'standalone',
            'orientation' => 'portrait-primary',
            'show_install_banner' => true,
            'install_banner_text' => 'نصب اپ سرزمین هارد روی صفحه اصلی',
            'install_help_android' => 'منوی کروم ← Install app / افزودن به صفحه اصلی',
            'install_help_ios' => 'Safari ← Share ← Add to Home Screen',
            'mobile_bottom_nav' => true,
            'storefront_bottom_nav' => true,
            'force_app_on_mobile' => false,
            'offline_cache' => true,
            'offline_message' => 'اتصال اینترنت برقرار نیست. صفحات ذخیره‌شده در دسترس‌اند.',
            'hero_enabled' => true,
            'hero_title' => 'سرزمین هارد',
            'hero_text' => 'هارد، SSD و تجهیزات ذخیره‌سازی با گارانتی معتبر',
            'hero_cta_label' => 'مشاهده محصولات',
            'hero_cta_url' => '/app/shop',
            'show_search' => true,
            'show_categories' => true,
            'show_featured' => true,
            'show_quick_links' => true,
            'featured_title' => 'پرفروش‌ها',
            'shop_title' => 'فروشگاه',
            'empty_products_text' => 'هنوز محصولی ثبت نشده است.',
            'nav_home_label' => 'خانه',
            'nav_shop_label' => 'فروشگاه',
            'nav_cart_label' => 'سبد',
            'nav_account_label' => 'حساب',
            'show_nav_home' => true,
            'show_nav_shop' => true,
            'show_nav_cart' => true,
            'show_nav_account' => true,
            'account_show_orders' => true,
            'account_show_wallet' => true,
            'account_show_tickets' => true,
            'account_show_profile' => true,
            'account_show_track' => true,
            'account_show_full_site' => true,
            'product_show_add_cart' => true,
            'product_show_buy_now' => true,
            'cart_show_checkout' => true,
            'animations' => true,
            'compact_cards' => false,
            'icon_192' => '/images/hdd-land-icon-192.png',
            'icon_512' => '/images/hdd-land-icon-512.png',
            'categories_limit' => 10,
            'products_limit' => 16,
            'shop_per_page' => 24,
            'quick_link_1_label' => 'استعلام گارانتی',
            'quick_link_1_url' => '/serial-check',
            'quick_link_2_label' => 'پیگیری سفارش',
            'quick_link_2_url' => '/orders/track',
            'quick_link_3_label' => 'پشتیبانی',
            'quick_link_3_url' => '/account/tickets',
            // Live sync from site template/plugins
            'sync_menu_from_site' => false,
            'sync_quick_links_from_theme' => true,
            'sync_brand_from_site' => true,
            // Legacy horizontal chip strip (desktop-like) — off by default
            'show_site_menu' => false,
            'menu_limit' => 12,
            // Dedicated right drawer menu for WebApp
            'drawer_menu_enabled' => true,
            'drawer_side' => 'right',
            'drawer_title' => 'منوی وب‌اپ',
            'drawer_subtitle' => 'دسترسی سریع فروشگاه موبایل',
            'drawer_show_brand' => true,
            'drawer_show_full_site' => true,
            'drawer_full_site_label' => 'نسخه کامل سایت',
            'drawer_full_site_url' => '/',
            'drawer_item_home' => true,
            'drawer_home_label' => 'خانه',
            'drawer_home_url' => '/app',
            'drawer_home_icon' => '⌂',
            'drawer_item_shop' => true,
            'drawer_shop_label' => 'فروشگاه',
            'drawer_shop_url' => '/app/shop',
            'drawer_shop_icon' => '▣',
            'drawer_item_cart' => true,
            'drawer_cart_label' => 'سبد خرید',
            'drawer_cart_url' => '/app/cart',
            'drawer_cart_icon' => '▢',
            'drawer_item_account' => true,
            'drawer_account_label' => 'حساب من',
            'drawer_account_url' => '/app/account',
            'drawer_account_icon' => '☺',
            'drawer_item_track' => true,
            'drawer_track_label' => 'پیگیری سفارش',
            'drawer_track_url' => '/orders/track',
            'drawer_track_icon' => '⌕',
            'drawer_item_warranty' => true,
            'drawer_warranty_label' => 'استعلام گارانتی',
            'drawer_warranty_url' => '/serial-check',
            'drawer_warranty_icon' => '⛨',
            'drawer_item_support' => true,
            'drawer_support_label' => 'پشتیبانی',
            'drawer_support_url' => '/account/tickets',
            'drawer_support_icon' => '✉',
            'drawer_item_contact' => true,
            'drawer_contact_label' => 'تماس با ما',
            'drawer_contact_url' => '/contact',
            'drawer_contact_icon' => '☎',
            'drawer_extra_links' => '',
            // Footer (sync from modern FooterConfig)
            'show_footer' => true,
            'sync_footer_from_site' => true,
            // Smart install
            'smart_install' => true,
            'hide_install_when_installed' => true,
            'install_only_mobile' => true,
            'installed_badge_text' => 'نصب‌شده روی این دستگاه',
            'install_ready_text' => 'آماده نصب روی گوشی',
        ];
    }

    /** @return array<string,mixed> */
    public static function settings(): array
    {
        return JsonSettings::get(self::SETTINGS_KEY, static::defaults());
    }

    public static function isEnabled(): bool
    {
        return ! empty(static::settings()['enabled']);
    }

    /** Resolve brand icon to a working public URL (designed logo). */
    public static function iconUrl(?string $path = null, string $fallback = 'images/hdd-land-icon-192.png'): string
    {
        $path = trim((string) ($path ?? ''));
        $legacyIcons = ['images/webapp-icon-192.png', 'images/webapp-icon-512.png', 'images/sarzamin-hard-icon.png'];
        if (in_array(ltrim($path, '/'), $legacyIcons, true)) {
            $path = '';
        }
        $officialIcon = str_contains($fallback, '512')
            ? '/images/hdd-land-icon-512.png'
            : '/images/hdd-land-icon-192.png';
        $candidates = [];
        if ($path !== '') {
            $candidates[] = $path;
        }
        $candidates[] = $officialIcon;
        $candidates[] = $fallback;

        foreach ($candidates as $c) {
            $c = trim((string) $c);
            if ($c === '') {
                continue;
            }
            if (str_starts_with($c, 'http://') || str_starts_with($c, 'https://')) {
                return $c;
            }
            // Ignore broken dynamic SVG fallback path from older configs
            if (str_contains($c, '/webapp/icon/')) {
                continue;
            }
            $rel = ltrim($c, '/');
            if (str_starts_with($rel, 'public/')) {
                $rel = substr($rel, 7);
            }
            $file = public_path($rel);
            if (is_file($file) && filesize($file) > 0) {
                return asset($rel);
            }
        }

        return asset($fallback);
    }

    /**
     * Resolve product image path the same way as Catalog Product::imageUrl():
     * prefer public/uploads/… (storefront media), then public/storage/….
     */
    public static function productImageUrl(?string $image = null): string
    {
        $image = trim((string) ($image ?? ''));
        if ($image === '') {
            return asset('product-placeholder.svg');
        }
        if (str_starts_with($image, 'http://') || str_starts_with($image, 'https://')) {
            return $image;
        }

        $path = ltrim(str_replace('\\', '/', $image), '/');
        foreach (['uploads/', 'storage/', ''] as $prefix) {
            $rel = $prefix.$path;
            if ($prefix === '' && (str_starts_with($path, 'uploads/') || str_starts_with($path, 'storage/'))) {
                $rel = $path;
            }
            if (is_file(public_path($rel))) {
                return asset($rel);
            }
        }

        // Default: uploads (matches live media layout even if file check fails)
        return asset('uploads/'.$path);
    }

    /** @param  array<string,mixed>  $data */
    public static function saveSettings(array $data): void
    {
        $color = fn ($v, $fallback) => preg_match('/^#[0-9a-fA-F]{6}$/', (string) $v) ? (string) $v : $fallback;
        $label = fn ($v, $max, $fallback = '') => (mb_substr(trim((string) $v), 0, $max) ?: $fallback);
        $url = function ($v, $fallback = '/') {
            $v = trim((string) $v);

            return $v === '' ? $fallback : mb_substr($v, 0, 200);
        };

        JsonSettings::save(self::SETTINGS_KEY, static::defaults(), $data, [
            'enabled', 'show_install_banner', 'mobile_bottom_nav', 'storefront_bottom_nav',
            'force_app_on_mobile', 'offline_cache', 'hero_enabled', 'show_search',
            'show_categories', 'show_featured', 'show_quick_links',
            'show_nav_home', 'show_nav_shop', 'show_nav_cart', 'show_nav_account',
            'account_show_orders', 'account_show_wallet', 'account_show_tickets',
            'account_show_profile', 'account_show_track', 'account_show_full_site',
            'product_show_add_cart', 'product_show_buy_now', 'cart_show_checkout',
            'animations', 'compact_cards',
            'sync_menu_from_site', 'sync_quick_links_from_theme', 'sync_brand_from_site',
            'show_site_menu', 'show_footer', 'sync_footer_from_site',
            'drawer_menu_enabled', 'drawer_show_brand', 'drawer_show_full_site',
            'drawer_item_home', 'drawer_item_shop', 'drawer_item_cart', 'drawer_item_account',
            'drawer_item_track', 'drawer_item_warranty', 'drawer_item_support', 'drawer_item_contact',
            'smart_install', 'hide_install_when_installed', 'install_only_mobile',
        ], [
            'app_name' => fn ($v) => $label($v, 60, 'سرزمین هارد'),
            'short_name' => fn ($v) => $label($v, 24, 'سرزمین‌هارد'),
            'description' => fn ($v) => $label($v, 200),
            'theme_color' => fn ($v) => $color($v, '#e23d12'),
            'background_color' => fn ($v) => $color($v, '#f4f6f9'),
            'surface_color' => fn ($v) => $color($v, '#ffffff'),
            'text_color' => fn ($v) => $color($v, '#1a1d23'),
            'start_url' => function ($v) {
                $v = trim((string) $v);
                if ($v === '' || ! str_starts_with($v, '/')) {
                    return '/app';
                }

                return mb_substr($v, 0, 120);
            },
            'display' => fn ($v) => in_array($v, ['standalone', 'fullscreen', 'minimal-ui', 'browser'], true) ? $v : 'standalone',
            'orientation' => fn ($v) => in_array($v, ['any', 'portrait', 'portrait-primary', 'landscape'], true) ? $v : 'portrait-primary',
            'install_banner_text' => fn ($v) => $label($v, 120),
            'install_help_android' => fn ($v) => $label($v, 160),
            'install_help_ios' => fn ($v) => $label($v, 160),
            'offline_message' => fn ($v) => $label($v, 200),
            'hero_title' => fn ($v) => $label($v, 80),
            'hero_text' => fn ($v) => $label($v, 200),
            'hero_cta_label' => fn ($v) => $label($v, 40),
            'hero_cta_url' => fn ($v) => $url($v, '/app/shop'),
            'featured_title' => fn ($v) => $label($v, 40, 'پرفروش‌ها'),
            'shop_title' => fn ($v) => $label($v, 40, 'فروشگاه'),
            'empty_products_text' => fn ($v) => $label($v, 120),
            'nav_home_label' => fn ($v) => $label($v, 20, 'خانه'),
            'nav_shop_label' => fn ($v) => $label($v, 20, 'فروشگاه'),
            'nav_cart_label' => fn ($v) => $label($v, 20, 'سبد'),
            'nav_account_label' => fn ($v) => $label($v, 20, 'حساب'),
            'icon_192' => fn ($v) => $label($v, 500),
            'icon_512' => fn ($v) => $label($v, 500),
            'categories_limit' => fn ($v) => max(0, min(24, (int) $v)),
            'products_limit' => fn ($v) => max(4, min(48, (int) $v)),
            'shop_per_page' => fn ($v) => max(8, min(60, (int) $v)),
            'quick_link_1_label' => fn ($v) => $label($v, 40),
            'quick_link_1_url' => fn ($v) => $url($v, '/serial-check'),
            'quick_link_2_label' => fn ($v) => $label($v, 40),
            'quick_link_2_url' => fn ($v) => $url($v, '/orders/track'),
            'quick_link_3_label' => fn ($v) => $label($v, 40),
            'quick_link_3_url' => fn ($v) => $url($v, '/account/tickets'),
            'menu_limit' => fn ($v) => max(4, min(24, (int) $v)),
            'installed_badge_text' => fn ($v) => $label($v, 80, 'نصب‌شده روی این دستگاه'),
            'install_ready_text' => fn ($v) => $label($v, 80, 'آماده نصب روی گوشی'),
            'drawer_side' => fn ($v) => in_array($v, ['right', 'left'], true) ? $v : 'right',
            'drawer_title' => fn ($v) => $label($v, 60, 'منوی وب‌اپ'),
            'drawer_subtitle' => fn ($v) => $label($v, 120, 'دسترسی سریع فروشگاه موبایل'),
            'drawer_full_site_label' => fn ($v) => $label($v, 40, 'نسخه کامل سایت'),
            'drawer_full_site_url' => fn ($v) => $url($v, '/'),
            'drawer_home_label' => fn ($v) => $label($v, 30, 'خانه'),
            'drawer_home_url' => fn ($v) => $url($v, '/app'),
            'drawer_home_icon' => fn ($v) => $label($v, 8, '⌂'),
            'drawer_shop_label' => fn ($v) => $label($v, 30, 'فروشگاه'),
            'drawer_shop_url' => fn ($v) => $url($v, '/app/shop'),
            'drawer_shop_icon' => fn ($v) => $label($v, 8, '▣'),
            'drawer_cart_label' => fn ($v) => $label($v, 30, 'سبد خرید'),
            'drawer_cart_url' => fn ($v) => $url($v, '/app/cart'),
            'drawer_cart_icon' => fn ($v) => $label($v, 8, '▢'),
            'drawer_account_label' => fn ($v) => $label($v, 30, 'حساب من'),
            'drawer_account_url' => fn ($v) => $url($v, '/app/account'),
            'drawer_account_icon' => fn ($v) => $label($v, 8, '☺'),
            'drawer_track_label' => fn ($v) => $label($v, 30, 'پیگیری سفارش'),
            'drawer_track_url' => fn ($v) => $url($v, '/orders/track'),
            'drawer_track_icon' => fn ($v) => $label($v, 8, '⌕'),
            'drawer_warranty_label' => fn ($v) => $label($v, 30, 'استعلام گارانتی'),
            'drawer_warranty_url' => fn ($v) => $url($v, '/serial-check'),
            'drawer_warranty_icon' => fn ($v) => $label($v, 8, '⛨'),
            'drawer_support_label' => fn ($v) => $label($v, 30, 'پشتیبانی'),
            'drawer_support_url' => fn ($v) => $url($v, '/account/tickets'),
            'drawer_support_icon' => fn ($v) => $label($v, 8, '✉'),
            'drawer_contact_label' => fn ($v) => $label($v, 30, 'تماس با ما'),
            'drawer_contact_url' => fn ($v) => $url($v, '/contact'),
            'drawer_contact_icon' => fn ($v) => $label($v, 8, '☎'),
            'drawer_extra_links' => fn ($v) => mb_substr(trim((string) $v), 0, 3000),
        ]);
    }

    /**
     * Dedicated WebApp drawer items from admin settings (not MegaMenu clone).
     *
     * @param  array<string,mixed>|null  $s
     * @return list<array{key:string,label:string,url:string,icon:string}>
     */
    public static function resolveDrawerMenu(?array $s = null): array
    {
        $s = $s ?? static::settings();
        if (empty($s['drawer_menu_enabled'])) {
            return [];
        }

        $keys = ['home', 'shop', 'cart', 'account', 'track', 'warranty', 'support', 'contact'];
        $out = [];
        foreach ($keys as $key) {
            if (empty($s['drawer_item_'.$key])) {
                continue;
            }
            $label = trim((string) ($s['drawer_'.$key.'_label'] ?? ''));
            $url = trim((string) ($s['drawer_'.$key.'_url'] ?? ''));
            if ($label === '' || $url === '') {
                continue;
            }
            $out[] = [
                'key' => $key,
                'label' => $label,
                'url' => $url,
                'icon' => trim((string) ($s['drawer_'.$key.'_icon'] ?? '•')) ?: '•',
            ];
        }

        $extra = (string) ($s['drawer_extra_links'] ?? '');
        foreach (preg_split('/\R/u', $extra) ?: [] as $line) {
            [$lab, $href, $ico] = array_pad(explode('|', $line, 3), 3, '');
            $lab = trim($lab);
            $href = trim($href);
            if ($lab === '' || $href === '' || $href === '#') {
                continue;
            }
            $out[] = [
                'key' => 'extra',
                'label' => mb_substr($lab, 0, 40),
                'url' => mb_substr($href, 0, 200),
                'icon' => trim($ico) !== '' ? mb_substr(trim($ico), 0, 8) : '•',
            ];
        }

        return $out;
    }
}
