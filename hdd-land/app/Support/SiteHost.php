<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Two public faces, one shop+studio database.
 * Receipt/ticket site stays on support.hdd-land.ir with its own database.
 */
class SiteHost
{
    public const RECEIPT_ORIGIN = 'https://support.hdd-land.ir';

    public static function host(?Request $request = null): string
    {
        $request ??= request();
        if ($request) {
            return strtolower(trim((string) $request->getHost()));
        }

        return strtolower((string) parse_url((string) config('app.url', 'https://hdd-land.ir'), PHP_URL_HOST));
    }

    public static function isReceiptHost(?Request $request = null): bool
    {
        $host = static::host($request);

        return $host === 'support.hdd-land.ir' || str_starts_with($host, 'support.');
    }

    public static function isShop(?Request $request = null): bool
    {
        if (static::isReceiptHost($request)) {
            return false;
        }
        $request ??= request();
        $host = static::host($request);
        if (str_starts_with($host, 'shop.')) {
            return true;
        }
        if ($request && $request->query('site') === 'shop') {
            return true;
        }

        return strtolower((string) env('FORCE_SITE', '')) === 'shop';
    }

    public static function isStudio(?Request $request = null): bool
    {
        return ! static::isShop($request) && ! static::isReceiptHost($request);
    }

    public static function shopHost(): string
    {
        $fromEnv = trim((string) env('SHOP_HOST', ''));

        return $fromEnv !== '' ? $fromEnv : 'shop.hdd-land.ir';
    }

    public static function studioHost(): string
    {
        $fromEnv = trim((string) env('STUDIO_HOST', ''));
        if ($fromEnv !== '') {
            return $fromEnv;
        }
        $appHost = (string) parse_url((string) config('app.url', 'https://hdd-land.ir'), PHP_URL_HOST);

        return $appHost !== '' ? $appHost : 'hdd-land.ir';
    }

    public static function scheme(): string
    {
        $url = (string) config('app.url', 'https://hdd-land.ir');

        return parse_url($url, PHP_URL_SCHEME) ?: 'https';
    }

    public static function shopUrl(string $path = '/'): string
    {
        return static::scheme().'://'.static::shopHost().static::normPath($path);
    }

    public static function studioUrl(string $path = '/'): string
    {
        return static::scheme().'://'.static::studioHost().static::normPath($path);
    }

    public static function receiptUrl(string $path = '/'): string
    {
        return rtrim(self::RECEIPT_ORIGIN, '/').static::normPath($path);
    }

    public static function recommendedSessionDomain(): string
    {
        return '.hdd-land.ir';
    }

    /** @return list<array{label:string,url:string,external?:bool}> */
    public static function studioMenu(): array
    {
        return [
            ['label' => 'طراحی سایت', 'url' => '/design'],
            ['label' => 'درباره ما', 'url' => '/about'],
            ['label' => 'حسابداری وب', 'url' => '/accounting'],
            ['label' => 'حسابداری کامپیوتر', 'url' => '/computer-accounting'],
            ['label' => 'پروژه AI', 'url' => '/ai'],
            ['label' => 'پروژه‌های سایت', 'url' => '/projects'],
            ['label' => 'نرم‌افزار قبض تحت وب', 'url' => '/receipt-software'],
        ];
    }

    /** @return list<array{label:string,url:string}> */
    public static function shopMenu(): array
    {
        return [
            ['label' => 'خانه', 'url' => '/'],
            ['label' => 'محصولات', 'url' => '/products'],
            ['label' => 'گارانتی', 'url' => '/serial-check'],
            ['label' => 'پیگیری سفارش', 'url' => '/orders/track'],
            ['label' => 'حساب', 'url' => '/account'],
            ['label' => 'تماس', 'url' => '/contact'],
        ];
    }

    /** Paths that belong on the parts shop host. */
    public static function isShopOnlyPath(string $path): bool
    {
        $path = '/'.ltrim(strtolower($path), '/');

        foreach ([
            '/products', '/product', '/category', '/categories',
            '/cart', '/checkout', '/serial-check', '/orders',
            '/app',
        ] as $prefix) {
            if ($path === $prefix || str_starts_with($path, $prefix.'/')) {
                return true;
            }
        }

        return (bool) preg_match('#^/account/(shop|orders|invoices|serials|preorders|wallet)(/|$)#', $path);
    }

    /** Studio marketing pages — redirect off the shop host. */
    public static function isStudioOnlyPath(string $path): bool
    {
        $path = '/'.ltrim(strtolower($path), '/');

        foreach (['/design', '/ai', '/accounting', '/computer-accounting', '/projects', '/receipt-software', '/repair-shop', '/sites', '/about'] as $prefix) {
            if ($path === $prefix || str_starts_with($path, $prefix.'/')) {
                return true;
            }
        }

        return false;
    }

    public static function isSharedPath(string $path): bool
    {
        $path = '/'.ltrim(strtolower($path), '/');

        foreach ([
            '/admin', '/staff', '/login', '/logout', '/register',
            '/password', '/sanctum', '/livewire',
        ] as $prefix) {
            if ($path === $prefix || str_starts_with($path, $prefix.'/')) {
                return true;
            }
        }

        return false;
    }

    protected static function normPath(string $path): string
    {
        $path = '/'.ltrim($path, '/');
        if ($path === '/') {
            return '/';
        }

        return rtrim($path, '/') ?: '/';
    }
}
