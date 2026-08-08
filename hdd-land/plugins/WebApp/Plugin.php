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
        return '2.2.0';
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
            'sync_menu_from_site' => true,
            'sync_quick_links_from_theme' => true,
            'sync_brand_from_site' => true,
            'show_site_menu' => true,
            'menu_limit' => 12,
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
        ]);
    }
}
