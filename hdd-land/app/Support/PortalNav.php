<?php

namespace App\Support;

/**
 * Shared navigation blueprint across storefront, WebApp (/app),
 * customer cabinet, staff panel, and admin cross-links.
 * Additive helper — does not replace MegaMenu admin or plugin schemas.
 */
class PortalNav
{
    /** @return list<array{label:string,url:string,children?:list<array{label:string,url:string}>}> */
    public static function storefront(): array
    {
        return [
            [
                'label' => 'خانه',
                'url' => '/',
                'children' => [],
            ],
            [
                'label' => 'محصولات',
                'url' => '/products',
                'children' => [
                    ['label' => 'همه محصولات', 'url' => '/products'],
                    ['label' => 'SSD', 'url' => '/products?part_type=ssd'],
                    ['label' => 'NVMe', 'url' => '/products?part_type=nvme'],
                    ['label' => 'هارد HDD', 'url' => '/products?part_type=hdd'],
                    ['label' => 'رم', 'url' => '/products?part_type=ram'],
                ],
            ],
            [
                'label' => 'پیگیری سفارش',
                'url' => '/orders/track',
                'children' => [],
            ],
            [
                'label' => 'استعلام گارانتی',
                'url' => '/serial-check',
                'children' => [],
            ],
            [
                'label' => 'پشتیبانی',
                'url' => '/account/tickets',
                'children' => [],
            ],
            [
                'label' => 'تماس با ما',
                'url' => '/contact',
                'children' => [],
            ],
        ];
    }

    /** Compact horizontal menu for WebApp top strip. */
    /** @return list<array{label:string,url:string,children:list<array{label:string,url:string}>}> */
    public static function webappSiteMenu(): array
    {
        return [
            ['label' => 'خانه', 'url' => '/app', 'children' => []],
            ['label' => 'فروشگاه', 'url' => '/app/shop', 'children' => []],
            ['label' => 'پیگیری سفارش', 'url' => '/orders/track', 'children' => []],
            ['label' => 'استعلام گارانتی', 'url' => '/serial-check', 'children' => []],
            ['label' => 'حساب', 'url' => '/app/account', 'children' => []],
        ];
    }

    /**
     * Customer cabinet groups — aligned with WebApp account shortcuts.
     *
     * @return list<array{title:string,items:list<array{label:string,url:string,match?:string,icon?:string}>}>
     */
    public static function customerGroups(): array
    {
        return [
            [
                'title' => 'نمای کلی',
                'items' => [
                    ['label' => 'داشبورد', 'url' => '/account', 'match' => 'account', 'icon' => '⌂'],
                ],
            ],
            [
                'title' => 'خرید و سفارش‌ها',
                'items' => [
                    ['label' => 'خرید محصول', 'url' => '/account/shop', 'match' => 'account/shop', 'icon' => '◎'],
                    ['label' => 'سفارش‌های من', 'url' => '/account/orders', 'match' => 'account/orders', 'icon' => '▣'],
                    ['label' => 'فاکتورها', 'url' => '/account/invoices', 'match' => 'account/invoices', 'icon' => '▤'],
                    ['label' => 'پیش‌خرید', 'url' => '/account/preorders', 'match' => 'account/preorders', 'icon' => '◷'],
                    ['label' => 'سبد خرید', 'url' => '/cart', 'icon' => '🛒'],
                    ['label' => 'پیگیری سفارش', 'url' => '/orders/track', 'icon' => '⌕'],
                ],
            ],
            [
                'title' => 'پشتیبانی و ارتباط',
                'items' => [
                    ['label' => 'تیکت پشتیبانی', 'url' => '/account/tickets', 'match' => 'account/tickets', 'icon' => '✉'],
                    ['label' => 'استعلام گارانتی', 'url' => '/serial-check', 'icon' => '⛨'],
                    ['label' => 'تاریخچه چت هوشمند', 'url' => '/account/chat-history', 'match' => 'account/chat-history', 'icon' => '✦'],
                ],
            ],
            [
                'title' => 'حساب و امنیت',
                'items' => [
                    ['label' => 'کیف پول', 'url' => '/account/wallet', 'match' => 'account/wallet', 'icon' => '◈'],
                    ['label' => 'مشخصات حساب', 'url' => '/account/profile', 'match' => 'account/profile', 'icon' => '☺'],
                    ['label' => 'تغییر رمز', 'url' => '/account/password', 'match' => 'account/password', 'icon' => '⚿'],
                    ['label' => 'امنیت و ورود دومرحله‌ای', 'url' => '/account/security', 'match' => 'account/security', 'icon' => '⛨'],
                    ['label' => 'تأیید شماره موبایل', 'url' => '/account/verify-phone', 'match' => 'account/verify-phone', 'icon' => '✓'],
                ],
            ],
            [
                'title' => 'دسترسی سریع',
                'items' => [
                    ['label' => 'بازگشت به فروشگاه', 'url' => '/', 'icon' => '⌂'],
                    ['label' => 'وب‌اپ موبایل', 'url' => '/app/account', 'icon' => '📱'],
                    ['label' => 'تماس با ما', 'url' => '/contact', 'icon' => '☎'],
                ],
            ],
        ];
    }

    /**
     * Staff sidebar — same labels as admin-facing ops where possible.
     *
     * @param  array<string,bool>  $perms
     * @return list<array{label:string,href:string,show:bool,active:bool}>
     */
    public static function staffItems(array $perms, string $path): array
    {
        $u = static fn (string $p) => url('/staff/'.ltrim($p, '/'));

        return [
            ['label' => 'داشبورد', 'href' => $u('/'), 'show' => true, 'active' => $path === 'staff'],
            ['label' => 'گزارش کار و سود', 'href' => $u('reports'), 'show' => ! empty($perms['reports']), 'active' => str_contains($path, 'staff/reports')],
            ['label' => 'سفارش‌ها', 'href' => $u('orders'), 'show' => ! empty($perms['orders']) || ! empty($perms['sales']), 'active' => str_contains($path, 'staff/orders')],
            ['label' => 'محصولات', 'href' => $u('products'), 'show' => ! empty($perms['products.view']), 'active' => str_contains($path, 'staff/products')],
            ['label' => 'سریال‌ها / گارانتی', 'href' => $u('serials'), 'show' => ! empty($perms['serials']) || ! empty($perms['sales']), 'active' => str_contains($path, 'staff/serials')],
            ['label' => 'فروش قطعه', 'href' => $u('sell'), 'show' => ! empty($perms['sales']) || ! empty($perms['orders']) || ! empty($perms['accounting']), 'active' => str_contains($path, 'staff/sell')],
            ['label' => 'پشتیبانی', 'href' => $u('tickets'), 'show' => ! empty($perms['support']), 'active' => str_contains($path, 'staff/tickets')],
            ['label' => 'حسابداری', 'href' => $u('accounting'), 'show' => ! empty($perms['accounting']), 'active' => str_contains($path, 'staff/accounting') && ! str_contains($path, 'staff/sell')],
        ];
    }

    /** Cross-portal links shown under staff/admin shortcuts. */
    /** @return list<array{label:string,url:string}> */
    public static function crossLinks(string $for = 'staff'): array
    {
        $all = [
            ['label' => 'مشاهده سایت', 'url' => '/'],
            ['label' => 'وب‌اپ فروشگاه', 'url' => '/app'],
            ['label' => 'کارتابل مشتری', 'url' => '/account'],
            ['label' => 'پنل کارمندان', 'url' => '/staff'],
            ['label' => 'پنل مدیریت', 'url' => '/admin'],
        ];

        return match ($for) {
            'staff' => array_values(array_filter($all, fn ($i) => ! in_array($i['url'], ['/staff'], true))),
            'customer' => array_values(array_filter($all, fn ($i) => in_array($i['url'], ['/', '/app', '/app/account'], true) || $i['url'] === '/app')),
            default => $all,
        };
    }

    public static function isDeadUrl(string $url): bool
    {
        $url = trim($url);
        if ($url === '' || $url === '#' || str_starts_with($url, 'javascript:')) {
            return true;
        }

        return (bool) preg_match('/^#+$/', $url);
    }

    public static function isPlaceholderLabel(string $label): bool
    {
        $label = trim(mb_strtolower($label));
        if ($label === '') {
            return true;
        }

        return in_array($label, ['زیرمنو', 'submenu', 'sub-menu', '-', '—', 'link'], true);
    }

    /** Remap storefront URLs to in-app destinations when useful. */
    public static function mapUrlForWebApp(string $url): string
    {
        $url = trim($url);
        if (static::isDeadUrl($url)) {
            return '/app';
        }

        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            $host = parse_url($url, PHP_URL_HOST);
            $path = parse_url($url, PHP_URL_PATH) ?: '/';
            $query = parse_url($url, PHP_URL_QUERY);
            $siteHost = parse_url((string) config('app.url', 'https://hdd-land.ir'), PHP_URL_HOST);
            if ($host && $siteHost && strcasecmp((string) $host, (string) $siteHost) !== 0) {
                return $url;
            }
            $url = $path.($query ? '?'.$query : '');
        }

        $path = parse_url($url, PHP_URL_PATH) ?: $url;
        $query = parse_url($url, PHP_URL_QUERY);
        $map = [
            '/' => '/app',
            '/products' => '/app/shop',
            '/account' => '/app/account',
            '/cart' => '/app/cart',
            '/checkout' => '/checkout',
        ];
        if (isset($map[$path])) {
            $mapped = $map[$path];
            // Keep product filters when pointing at shop
            if ($path === '/products' && $query) {
                return $mapped.'?'.$query;
            }

            return $mapped;
        }

        return $url;
    }

    /** Normalize footer / menu link for WebApp compact footer. */
    public static function mapFooterUrlForWebApp(string $url): string
    {
        return static::mapUrlForWebApp($url);
    }
}
