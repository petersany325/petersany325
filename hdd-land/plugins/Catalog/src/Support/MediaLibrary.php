<?php

namespace Plugins\Catalog\src\Support;

use App\Support\JsonSettings;

class MediaLibrary
{
    public const SETTINGS_KEY = 'media_library_settings';

    /** @return array<string,mixed> */
    public static function defaults(): array
    {
        return [
            'enabled' => true,
            'allow_mkdir' => true,
            'allow_upload' => true,
            'allow_delete' => true,
            'allow_move' => true,
            'allow_copy' => true,
            'allow_rename' => true,
            'max_upload_kb' => 20480,
            'allowed_extensions' => 'jpg,jpeg,png,webp,gif,mp4,webm,mov,avi,pdf,zip',
            'root_folder' => 'media',
        ];
    }

    /** @return array<string,mixed> */
    public static function settings(): array
    {
        $settings = JsonSettings::get(self::SETTINGS_KEY, static::defaults());

        // Folder management is a core part of the media manager. Keep these
        // actions available even when an older saved settings record predates
        // the two options or has them disabled.
        $settings['allow_delete'] = true;
        $settings['allow_rename'] = true;
        $settings['allow_upload'] = true;
        $settings['allow_mkdir'] = true;
        $settings['enabled'] = ($settings['enabled'] ?? true) ? true : false;

        return $settings;
    }

    /** @param  array<string,mixed>  $data */
    public static function saveSettings(array $data): void
    {
        JsonSettings::save(self::SETTINGS_KEY, static::defaults(), $data, [
            'enabled', 'allow_mkdir', 'allow_upload', 'allow_delete', 'allow_move', 'allow_copy', 'allow_rename',
        ], [
            'max_upload_kb' => fn ($v) => max(100, min(20480, (int) $v)),
            'allowed_extensions' => fn ($v) => preg_replace('/[^a-z0-9,]/i', '', strtolower((string) $v)) ?: 'jpg,jpeg,png,webp,gif',
            'root_folder' => fn ($v) => preg_replace('/[^a-z0-9_\-]/i', '', (string) $v) ?: 'media',
        ]);
    }

    public static function can(string $action): bool
    {
        $s = static::settings();
        if (empty($s['enabled'])) {
            return false;
        }
        $map = [
            'mkdir' => 'allow_mkdir',
            'upload' => 'allow_upload',
            'delete' => 'allow_delete',
            'move' => 'allow_move',
            'copy' => 'allow_copy',
            'rename' => 'allow_rename',
        ];

        return ! empty($s[$map[$action] ?? '']) || $action === 'browse';
    }

    public static function rootRelative(): string
    {
        $s = static::settings();

        return trim((string) ($s['root_folder'] ?? 'media'), '/');
    }

    public static function storageRoot(): string
    {
        $root = storage_path('app/public/'.static::rootRelative());
        if (! is_dir($root)) {
            @mkdir($root, 0755, true);
        }

        return $root;
    }

    public static function publicRoot(): string
    {
        $root = public_path('uploads/'.static::rootRelative());
        if (! is_dir($root)) {
            @mkdir($root, 0755, true);
        }

        return $root;
    }

    public static function isValidUtf8(string $value): bool
    {
        if (function_exists('mb_check_encoding')) {
            return mb_check_encoding($value, 'UTF-8');
        }

        return preg_match('//u', $value) === 1;
    }

    public static function encodeRawSegment(string $binaryName): string
    {
        return 'raw_'.bin2hex($binaryName);
    }

    public static function decodeRawSegment(string $segment): ?string
    {
        if (! preg_match('/^raw_([0-9a-fA-F]+)$/', $segment, $m)) {
            return null;
        }
        if (strlen($m[1]) % 2 !== 0) {
            return null;
        }
        $raw = @hex2bin($m[1]);

        return $raw === false ? null : $raw;
    }

    public static function displayName(string $binaryName): string
    {
        if (static::isValidUtf8($binaryName) && preg_match('/^[a-zA-Z0-9_\-\. \x{0600}-\x{06FF}]+$/u', $binaryName)) {
            return $binaryName;
        }

        return 'نام‌خراب-'.substr(bin2hex($binaryName), 0, 8);
    }

    /**
     * Encode a filesystem-relative path (may contain broken bytes) into a
     * transport-safe relative path for admin UI / JSON.
     */
    public static function encodeRel(string $binaryRel): string
    {
        $binaryRel = str_replace('\\', '/', $binaryRel);
        $binaryRel = trim($binaryRel, '/');
        if ($binaryRel === '') {
            return '';
        }
        $parts = [];
        foreach (explode('/', $binaryRel) as $p) {
            if ($p === '') {
                continue;
            }
            if (static::isValidUtf8($p) && preg_match('/^[a-zA-Z0-9_\-\. \x{0600}-\x{06FF}]+$/u', $p)) {
                $parts[] = $p;
            } else {
                $parts[] = static::encodeRawSegment($p);
            }
        }

        return implode('/', $parts);
    }

    /**
     * Decode a transport-safe relative path back to filesystem segment names.
     *
     * @return list<string>
     */
    public static function decodeRelSegments(string $path): array
    {
        $path = str_replace('\\', '/', (string) $path);
        $path = trim($path, '/');
        if ($path === '') {
            return [];
        }
        $parts = [];
        foreach (explode('/', $path) as $p) {
            if ($p === '' || $p === '.') {
                continue;
            }
            if ($p === '..') {
                array_pop($parts);
                continue;
            }
            $raw = static::decodeRawSegment($p);
            if ($raw !== null) {
                $parts[] = $raw;
                continue;
            }
            if (! static::isValidUtf8($p)) {
                continue;
            }
            if (! preg_match('/^[a-zA-Z0-9_\-\. \x{0600}-\x{06FF}]+$/u', $p)) {
                continue;
            }
            $parts[] = $p;
        }

        return $parts;
    }

    /**
     * Normalize a transport-safe relative path (no leading slash, no ..).
     * Broken on-disk names are represented as raw_<hex> segments.
     */
    public static function normalizeRel(?string $path): string
    {
        $path = str_replace('\\', '/', (string) $path);
        $path = trim($path, '/');
        if ($path === '') {
            return '';
        }

        $parts = [];
        foreach (explode('/', $path) as $p) {
            if ($p === '' || $p === '.') {
                continue;
            }
            if ($p === '..') {
                array_pop($parts);
                continue;
            }
            if (static::decodeRawSegment($p) !== null) {
                $parts[] = strtolower($p);
                continue;
            }
            if (! static::isValidUtf8($p)) {
                continue;
            }
            if (! preg_match('/^[a-zA-Z0-9_\-\. \x{0600}-\x{06FF}]+$/u', $p)) {
                continue;
            }
            $parts[] = $p;
        }

        return implode('/', $parts);
    }

    /**
     * Build absolute filesystem path under storage root from a transport-safe rel path.
     */
    public static function absolute(string $rel = ''): string
    {
        $segments = static::decodeRelSegments($rel);
        $base = realpath(static::storageRoot()) ?: static::storageRoot();
        $full = $base;
        foreach ($segments as $seg) {
            $full .= DIRECTORY_SEPARATOR.$seg;
        }
        $real = realpath($full);
        if ($real === false) {
            $parent = dirname($full);
            $parentReal = realpath($parent);
            if ($parentReal === false || ! static::isInside($parentReal, $base)) {
                throw new \RuntimeException('مسیر نامعتبر است.');
            }

            return $full;
        }
        if (! static::isInside($real, $base)) {
            throw new \RuntimeException('دسترسی به این مسیر مجاز نیست.');
        }

        return $real;
    }

    /** Absolute path under public uploads mirror. */
    public static function publicAbsolute(string $rel = ''): string
    {
        $segments = static::decodeRelSegments($rel);
        $base = realpath(static::publicRoot()) ?: static::publicRoot();
        $full = $base;
        foreach ($segments as $seg) {
            $full .= DIRECTORY_SEPARATOR.$seg;
        }

        return $full;
    }

    public static function urlFor(string $rel): string
    {
        $segments = static::decodeRelSegments($rel);
        // Broken byte names cannot be exposed as public URLs safely.
        foreach ($segments as $seg) {
            if (! static::isValidUtf8($seg)) {
                return asset('product-placeholder.svg');
            }
        }
        $relFs = implode('/', $segments);
        $root = static::rootRelative();
        $path = $root.($relFs !== '' ? '/'.$relFs : '');
        if (is_file(public_path('uploads/'.$path))) {
            return asset('uploads/'.$path);
        }

        return asset('storage/'.$path);
    }

    public static function mirrorToPublic(string $absStorageFile): void
    {
        $base = realpath(static::storageRoot()) ?: static::storageRoot();
        $real = realpath($absStorageFile);
        if ($real === false || ! static::isInside($real, $base)) {
            return;
        }
        $rel = ltrim(str_replace('\\', '/', substr($real, strlen(rtrim($base, DIRECTORY_SEPARATOR)))), '/');
        $dest = static::publicRoot().($rel !== '' ? DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $rel) : '');
        $dir = dirname($dest);
        if (! is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        @copy($real, $dest);
    }

    /**
     * Remove empty directories whose names are not valid UTF-8 (or are pure mojibake),
     * from both storage and public mirrors. Returns number of removed dirs.
     */
    public static function purgeEmptyBrokenFolders(): int
    {
        $removed = 0;
        foreach ([static::storageRoot(), static::publicRoot()] as $root) {
            if (! is_dir($root)) {
                continue;
            }
            foreach (scandir($root) ?: [] as $name) {
                if ($name === '.' || $name === '..') {
                    continue;
                }
                $full = $root.DIRECTORY_SEPARATOR.$name;
                if (! is_dir($full)) {
                    continue;
                }
                $broken = ! static::isValidUtf8($name)
                    || ! preg_match('/^[a-zA-Z0-9_\-\. \x{0600}-\x{06FF}]+$/u', $name)
                    || preg_match('/^[+?؟]+$/u', $name);
                if (! $broken) {
                    continue;
                }
                $children = array_values(array_diff(scandir($full) ?: [], ['.', '..']));
                if ($children !== []) {
                    continue;
                }
                if (@rmdir($full)) {
                    $removed++;
                }
            }
        }

        return $removed;
    }

    /** Suggest a safe folder name (keeps Persian when valid UTF-8). */
    public static function safeFolderName(string $name): string
    {
        $name = trim(str_replace(['\\', '/'], '', $name));
        if ($name === '') {
            return '';
        }
        if (! static::isValidUtf8($name)) {
            $name = preg_replace('/[^\x20-\x7E]+/', '-', $name) ?: '';
        }
        $name = preg_replace('/\s+/u', '-', $name) ?? $name;
        $name = preg_replace('/[^a-zA-Z0-9_\-\.\x{0600}-\x{06FF}]+/u', '-', $name) ?? $name;
        $name = trim($name, '.-');

        return $name;
    }

    /** @return list<string> */
    public static function allowedExt(): array
    {
        $s = static::settings();

        return array_values(array_filter(array_map('trim', explode(',', (string) ($s['allowed_extensions'] ?? '')))));
    }

    public static function isImageExt(string $ext): bool
    {
        return in_array(strtolower($ext), ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg'], true);
    }

    public static function isVideoExt(string $ext): bool
    {
        return in_array(strtolower($ext), ['mp4', 'webm', 'mov', 'avi', 'mkv'], true);
    }

    public static function isAllowedFile(string $filename): bool
    {
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        // SVG is active XML and may execute script when served directly.
        return $ext !== '' && $ext !== 'svg' && in_array($ext, static::allowedExt(), true);
    }

    private static function isInside(string $path, string $base): bool
    {
        $normalize = static fn (string $value): string => strtolower(rtrim(str_replace('\\', '/', $value), '/'));
        $path = $normalize($path);
        $base = $normalize($base);

        return $path === $base || str_starts_with($path, $base.'/');
    }
}
