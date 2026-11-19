<?php

namespace App\Support;

/**
 * HTML cache for the public design pages. The CSRF token is stored as a
 * placeholder and filled in on each request so forms stay valid.
 */
class DesignPageCache
{
    public static function get(string $path): ?string
    {
        $file = self::file($path);
        if (! is_file($file)) {
            return null;
        }
        $stamp = self::stamp();
        if ($stamp > (int) filemtime($file) || (time() - (int) filemtime($file)) > 1800) {
            return null;
        }
        $html = file_get_contents($file);

        return is_string($html) && strlen($html) > 8000 ? $html : null;
    }

    public static function put(string $path, string $html): void
    {
        if (strlen($html) < 8000 || ! str_contains($html, '%%CSRF%%') || ! self::trustedHost() || self::hasForeignAppUrl($html)) {
            return;
        }
        $html = str_ireplace(
            ['https://www.hdd-land.ir', 'http://www.hdd-land.ir', 'http://hdd-land.ir'],
            'https://hdd-land.ir',
            $html
        );
        $dir = dirname(self::file($path));
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents(self::file($path), $html, LOCK_EX);
    }

    public static function forget(): void
    {
        foreach (glob(storage_path('framework/cache/design-pages/*.html')) ?: [] as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }
    }

    private static function file(string $path): string
    {
        $path = '/'.trim($path, '/');

        return storage_path('framework/cache/design-pages/'.sha1($path).'.html');
    }

    private static function stamp(): int
    {
        $files = [
            base_path('app/Support/StudioDesignCopy.php'),
            base_path('app/Support/DesignPageCache.php'),
            base_path('public/css/studio-agency.css'),
        ];
        $stamp = 0;
        foreach ($files as $file) {
            if (is_file($file)) {
                $stamp = max($stamp, (int) filemtime($file));
            }
        }

        return $stamp;
    }

    /** Only the public site may write the shared cache. A forged Host must not. */
    private static function trustedHost(): bool
    {
        $host = strtolower((string) preg_replace('/:\d+$/', '', (string) request()->getHttpHost()));

        return in_array($host, ['hdd-land.ir', 'www.hdd-land.ir'], true);
    }

    /** Form and asset URLs must stay on this site. Other hosts, such as fonts, are left alone. */
    private static function hasForeignAppUrl(string $html): bool
    {
        return (bool) preg_match('#https?://(?!(?:www\.)?hdd-land\.ir)[^"\'\s>]+/(?:design(?:/|$)|css/|images/)#i', $html);
    }
}
