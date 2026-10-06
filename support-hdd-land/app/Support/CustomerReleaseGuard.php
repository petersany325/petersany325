<?php

namespace App\Support;

use ZipArchive;

/**
 * Hard gate: seller-only assets (SEO / marketing hub) must never enter customer update ZIPs.
 */
class CustomerReleaseGuard
{
    /**
     * Exact relative paths that are seller marketing/SEO only.
     *
     * @return list<string>
     */
    public static function sellerOnlyExactPaths(): array
    {
        return [
            'app/Support/SeoSettings.php',
            'app/Http/Controllers/SeoController.php',
            'resources/views/partials/seo-meta.blade.php',
            'public/_fix500.php',
            'tools/_seo_customer_deploy_153.php',
            'tools/releases/README-1.3.53.md',
            'tools/releases/hddland-1.3.53.zip',
        ];
    }

    /**
     * Path prefixes / substrings that mark seller-only SEO packaging.
     *
     * @return list<string>
     */
    public static function sellerOnlyPatterns(): array
    {
        return [
            'seo-meta',
            'SeoSettings',
            'SeoController',
            '/seo/',
            'settings/seo',
            'images/seo/',
            'tools/customer-apex/',
            '_seo_',
            'hddland-1.3.53',
        ];
    }

    public static function isSellerOnlyPath(string $rel): bool
    {
        $rel = str_replace('\\', '/', ltrim(trim($rel), '/'));
        if ($rel === '') {
            return false;
        }
        foreach (self::sellerOnlyExactPaths() as $exact) {
            if ($rel === $exact) {
                return true;
            }
        }
        $base = basename($rel);
        if (preg_match('/(^|[\\/_-])seo([\\._-]|$)/i', $rel) || preg_match('/^seo/i', $base)) {
            // Keep non-SEO files that merely mention "settings" etc.; match seo token in path/name.
            if (str_contains(strtolower($rel), 'seo')) {
                return true;
            }
        }
        foreach (self::sellerOnlyPatterns() as $pat) {
            if (str_contains($rel, $pat) || str_contains(strtolower($rel), strtolower($pat))) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<string>  $files
     * @return array{kept:list<string>,stripped:list<string>}
     */
    public static function filterCustomerFiles(array $files): array
    {
        $kept = [];
        $stripped = [];
        foreach ($files as $rel) {
            $rel = str_replace('\\', '/', ltrim(trim((string) $rel), '/'));
            if ($rel === '') {
                continue;
            }
            if (self::isSellerOnlyPath($rel)) {
                $stripped[] = $rel;
            } else {
                $kept[] = $rel;
            }
        }

        return ['kept' => array_values(array_unique($kept)), 'stripped' => array_values(array_unique($stripped))];
    }

    /**
     * Inspect a customer release ZIP; reject if it only contains seller-only SEO files,
     * or rewrite a cleaned copy without those paths.
     *
     * @return array{ok:bool,message:string,path?:string,sha256?:string,stripped?:list<string>}
     */
    public static function scrubCustomerZip(string $zipPath): array
    {
        if (! is_file($zipPath)) {
            return ['ok' => false, 'message' => 'فایل ZIP پیدا نشد.'];
        }

        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            return ['ok' => false, 'message' => 'باز کردن ZIP ناموفق بود.'];
        }

        $all = [];
        $blocked = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = (string) $zip->getNameIndex($i);
            $name = str_replace('\\', '/', $name);
            if ($name === '' || str_ends_with($name, '/')) {
                continue;
            }
            // Ignore nested folder prefixes like support-hdd-land/
            $rel = $name;
            if (str_contains($rel, 'support-hdd-land/')) {
                $rel = substr($rel, strpos($rel, 'support-hdd-land/') + strlen('support-hdd-land/'));
            }
            $all[] = $rel;
            if (self::isSellerOnlyPath($rel) || self::isSellerOnlyPath($name)) {
                $blocked[] = $rel;
            }
        }

        if ($blocked === []) {
            $zip->close();
            $sha = hash_file('sha256', $zipPath) ?: '';

            return ['ok' => true, 'message' => 'OK', 'path' => $zipPath, 'sha256' => $sha, 'stripped' => []];
        }

        // Rebuild without seller-only entries
        $tmp = $zipPath.'.scrub-'.bin2hex(random_bytes(3)).'.zip';
        $out = new ZipArchive();
        if ($out->open($tmp, ZipArchive::CREATE) !== true) {
            $zip->close();

            return ['ok' => false, 'message' => 'ساخت ZIP پاک‌شده ناموفق بود.', 'stripped' => $blocked];
        }

        $keptCount = 0;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = (string) $zip->getNameIndex($i);
            $norm = str_replace('\\', '/', $name);
            if ($norm === '' || str_ends_with($norm, '/')) {
                continue;
            }
            $rel = $norm;
            if (str_contains($rel, 'support-hdd-land/')) {
                $rel = substr($rel, strpos($rel, 'support-hdd-land/') + strlen('support-hdd-land/'));
            }
            if (self::isSellerOnlyPath($rel) || self::isSellerOnlyPath($norm)) {
                continue;
            }
            $content = $zip->getFromIndex($i);
            if ($content === false) {
                continue;
            }
            $out->addFromString($rel, $content);
            $keptCount++;
        }
        $zip->close();
        $out->addFromString(
            'RELEASE_SEO_STRIPPED.txt',
            "stripped=".implode(',', $blocked)."\nnote=SEO/seller-only paths removed from customer package\n"
        );
        $out->close();

        if ($keptCount < 1) {
            @unlink($tmp);

            return [
                'ok' => false,
                'message' => 'این بسته فقط فایل‌های SEO/فروشنده دارد و برای آپدیت مشتری مجاز نیست.',
                'stripped' => $blocked,
            ];
        }

        @unlink($zipPath);
        if (! @rename($tmp, $zipPath)) {
            @copy($tmp, $zipPath);
            @unlink($tmp);
        }

        return [
            'ok' => true,
            'message' => 'فایل‌های SEO از بسته مشتری حذف شد ('.count($blocked).' مورد).',
            'path' => $zipPath,
            'sha256' => hash_file('sha256', $zipPath) ?: '',
            'stripped' => $blocked,
        ];
    }
}
