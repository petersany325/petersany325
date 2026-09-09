<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;
use ZipArchive;

/**
 * Customer-side live update: check seller license server, download ZIP, overlay files, migrate.
 *
 * DATA SAFETY (hard rule): never overwrite or delete user/business data.
 * Protected forever: .env*, storage/ (photos, uploads, sessions, logs), vendor/,
 * node_modules/, and local sqlite dumps. Code under database/migrations MAY update.
 */
class AppUpdateService
{
    private const VERSION_FILE = 'installed_version.json';

    private const SKIP_ROOT_NAMES = [
        '.env', '.env.backup', '.env.bak', '.env.bak-inst', '.env.bak-phase1',
        'storage', 'vendor', 'node_modules', '.git',
    ];

    private const SKIP_PREFIXES = [
        'storage/',
        'vendor/',
        'node_modules/',
        'bootstrap/cache/',
        '.git/',
    ];

    public function __construct(
        private SystemMaintenanceService $maintenance,
    ) {
    }

    public function installedVersion(): string
    {
        $path = storage_path('app/'.self::VERSION_FILE);
        if (is_file($path)) {
            $json = json_decode((string) file_get_contents($path), true);
            $v = is_array($json) ? trim((string) ($json['version'] ?? '')) : '';
            if ($v !== '' && preg_match('/^\d+\.\d+/', $v)) {
                return $v;
            }
        }

        return (string) config('updates.version', '1.0.0');
    }

    public function writeInstalledVersion(string $version, ?array $meta = null): void
    {
        $payload = array_merge([
            'version' => $version,
            'updated_at' => now()->toIso8601String(),
        ], $meta ?? []);

        file_put_contents(
            storage_path('app/'.self::VERSION_FILE),
            json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
        );
        Cache::forget($this->bannerCacheKey());
    }

    /**
     * @return array{
     *   ok:bool,
     *   has_update:bool,
     *   current:string,
     *   latest:?string,
     *   changelog:array<int,string>,
     *   released_at:?string,
     *   message:string,
     *   server?:string
     * }
     */
    public function checkForUpdate(bool $force = false): array
    {
        $current = $this->installedVersion();
        $channel = (string) config('updates.channel', 'stable');
        $server = rtrim((string) config('license.server', ''), '/');

        if ($server === '') {
            return [
                'ok' => false,
                'has_update' => false,
                'current' => $current,
                'latest' => null,
                'changelog' => [],
                'released_at' => null,
                'message' => 'آدرس سرور لایسنس تنظیم نشده است.',
            ];
        }

        $cacheKey = 'app_update_check_'.sha1($server.'|'.$channel.'|'.$current);
        if (! $force) {
            $cached = Cache::get($cacheKey);
            if (is_array($cached)) {
                return $cached;
            }
        }

        $key = trim((string) config('license.key'));
        $token = (string) config('license.token');
        $domain = \App\Models\ProductLicense::normalizeDomain(
            (string) (config('license.domain') ?: request()->getHost())
        );

        // Seller host (no LICENSE_KEY): still can check own manifest locally.
        if ($key === '') {
            $local = $this->localLatest($channel);
            $result = $this->compareResult($current, $local, $server, 'بررسی از مخزن محلی فروشنده.');
            Cache::put($cacheKey, $result, now()->addSeconds((int) config('updates.banner_cache_seconds', 120)));

            return $result;
        }

        try {
            $response = Http::timeout(20)
                ->asForm()
                ->acceptJson()
                ->post($server.'/license/updates/latest', [
                    'license_key' => $key,
                    'domain' => $domain,
                    'token' => $token,
                    'version' => $current,
                    'channel' => $channel,
                    'product' => (string) config('updates.product', 'hddland-repair'),
                ]);

            $json = $response->json();
            if (! is_array($json) || ($json['ok'] ?? false) !== true) {
                $msg = is_array($json)
                    ? (string) ($json['message'] ?? 'پاسخ نامعتبر از سرور آپدیت.')
                    : 'ارتباط با سرور آپدیت برقرار نشد (HTTP '.$response->status().').';

                return [
                    'ok' => false,
                    'has_update' => false,
                    'current' => $current,
                    'latest' => null,
                    'changelog' => [],
                    'released_at' => null,
                    'message' => $msg,
                    'server' => $server,
                ];
            }

            $latest = isset($json['latest']) ? (string) $json['latest'] : null;
            $changelog = is_array($json['changelog'] ?? null)
                ? array_values(array_map('strval', $json['changelog']))
                : [];
            $releasedAt = isset($json['released_at']) ? (string) $json['released_at'] : null;
            $has = $latest !== null && $latest !== '' && $this->versionCompare($latest, $current) > 0;

            $result = [
                'ok' => true,
                'has_update' => $has,
                'current' => $current,
                'latest' => $latest,
                'changelog' => $changelog,
                'released_at' => $releasedAt,
                'message' => $has
                    ? ('نسخه جدید '.$latest.' آماده است.')
                    : 'نرم‌افزار به‌روز است.',
                'server' => $server,
                'sha256' => isset($json['sha256']) ? (string) $json['sha256'] : null,
            ];

            Cache::put($cacheKey, $result, now()->addSeconds((int) config('updates.banner_cache_seconds', 120)));

            return $result;
        } catch (Throwable $e) {
            Log::warning('app_update_check_failed', ['error' => $e->getMessage()]);

            return [
                'ok' => false,
                'has_update' => false,
                'current' => $current,
                'latest' => null,
                'changelog' => [],
                'released_at' => null,
                'message' => 'خطا در بررسی آپدیت: '.$e->getMessage(),
                'server' => $server,
            ];
        }
    }

    /**
     * Download + overlay + migrate + clear caches.
     *
     * @return array{ok:bool,message:string,details?:array<int,string>,version?:string}
     */
    public function applyLatestUpdate(): array
    {
        @set_time_limit(600);
        @ini_set('memory_limit', '512M');

        $details = [];
        $check = $this->checkForUpdate(true);
        if (! ($check['ok'] ?? false)) {
            return ['ok' => false, 'message' => $check['message'] ?? 'بررسی آپدیت ناموفق.', 'details' => $details];
        }
        if (! ($check['has_update'] ?? false)) {
            return ['ok' => true, 'message' => 'نسخه جدیدتری وجود ندارد.', 'details' => $details, 'version' => $check['current']];
        }

        $targetVersion = (string) $check['latest'];
        $details[] = 'نسخه فعلی: '.$check['current'];
        $details[] = 'نسخه هدف: '.$targetVersion;

        $key = trim((string) config('license.key'));
        $zipPath = null;

        try {
            if ($key === '') {
                // Seller applying from local releases folder
                $local = $this->localLatest((string) config('updates.channel', 'stable'));
                $file = (string) ($local['file'] ?? '');
                $zipPath = $file !== '' ? storage_path('app/releases/'.$file) : '';
                if (! is_file($zipPath)) {
                    return ['ok' => false, 'message' => 'فایل ZIP آپدیت در مخزن محلی یافت نشد.', 'details' => $details];
                }
                $expectedSha = (string) ($local['sha256'] ?? '');
            } else {
                $download = $this->downloadFromServer($targetVersion);
                if (! ($download['ok'] ?? false)) {
                    return [
                        'ok' => false,
                        'message' => $download['message'] ?? 'دانلود آپدیت ناموفق.',
                        'details' => array_merge($details, $download['details'] ?? []),
                    ];
                }
                $zipPath = (string) $download['path'];
                $expectedSha = (string) ($download['sha256'] ?? ($check['sha256'] ?? ''));
                $details = array_merge($details, $download['details'] ?? []);
            }

            if ($expectedSha !== '') {
                $actual = hash_file('sha256', $zipPath);
                if (! hash_equals(strtolower($expectedSha), strtolower((string) $actual))) {
                    @unlink($zipPath);

                    return [
                        'ok' => false,
                        'message' => 'Checksum فایل آپدیت مطابقت ندارد — نصب لغو شد.',
                        'details' => array_merge($details, ['expected='.$expectedSha, 'actual='.$actual]),
                    ];
                }
                $details[] = '✓ صحت فایل (SHA-256) تأیید شد';
            }

            $overlay = $this->overlayZip($zipPath);
            $details = array_merge($details, $overlay['details'] ?? []);
            if (! ($overlay['ok'] ?? false)) {
                return [
                    'ok' => false,
                    'message' => $overlay['message'] ?? 'استخراج/کپی فایل‌ها ناموفق.',
                    'details' => $details,
                ];
            }

            $migrate = $this->maintenance->runPendingMigrations();
            $details = array_merge($details, ['— مایگریشن —'], $migrate['details'] ?? []);

            $cache = $this->maintenance->clearCaches();
            $details = array_merge($details, ['— پاک‌سازی کش —'], $cache['details'] ?? []);

            $safety = $this->assertUserDataIntact();
            $details = array_merge($details, ['— سلامت داده کاربری —'], $safety['details'] ?? []);
            if (! ($safety['ok'] ?? false)) {
                return [
                    'ok' => false,
                    'message' => 'آپدیت فایل‌ها انجام شد ولی بررسی سلامت داده ناموفق بود — داده را دستی چک کنید.',
                    'details' => $details,
                    'version' => $targetVersion,
                ];
            }

            $this->writeInstalledVersion($targetVersion, [
                'previous' => $check['current'],
                'changelog' => $check['changelog'] ?? [],
            ]);
            $details[] = '✓ نسخه نصب‌شده به '.$targetVersion.' به‌روز شد';

            Cache::forget('app_update_check_'.sha1(
                rtrim((string) config('license.server'), '/').'|'
                .(string) config('updates.channel', 'stable').'|'
                .$check['current']
            ));

            $ok = ($migrate['ok'] ?? false) !== false;

            return [
                'ok' => $ok,
                'message' => $ok
                    ? ('آپدیت به نسخه '.$targetVersion.' با موفقیت نصب شد.')
                    : ('فایل‌ها کپی شدند ولی مایگریشن هشدار داشت — جزئیات را ببینید.'),
                'details' => $details,
                'version' => $targetVersion,
            ];
        } catch (Throwable $e) {
            Log::error('app_update_apply_failed', ['error' => $e->getMessage()]);

            return [
                'ok' => false,
                'message' => 'نصب آپدیت متوقف شد: '.$e->getMessage(),
                'details' => $details,
            ];
        } finally {
            if ($zipPath && str_contains($zipPath, DIRECTORY_SEPARATOR.'tmp'.DIRECTORY_SEPARATOR)
                && is_file($zipPath)) {
                @unlink($zipPath);
            }
        }
    }

    /**
     * Post-update guard: confirm protected paths and DB still reachable.
     *
     * @return array{ok:bool,details:array<int,string>}
     */
    public function assertUserDataIntact(): array
    {
        $details = [];
        $ok = true;

        $env = base_path('.env');
        if (! is_file($env)) {
            $ok = false;
            $details[] = '✗ فایل .env پیدا نشد';
        } else {
            $details[] = '✓ .env محفوظ است';
        }

        $storage = storage_path('app');
        if (! is_dir($storage)) {
            $ok = false;
            $details[] = '✗ پوشه storage/app موجود نیست';
        } else {
            $details[] = '✓ storage/app موجود است (عکس و آپلودها)';
        }

        $photoRoot = storage_path('app/remote-part-preorders');
        $photoRootPrivate = storage_path('app/private/remote-part-preorders');
        $n = 0;
        try {
            if (class_exists(\Illuminate\Support\Facades\Storage::class)) {
                $n = count(\Illuminate\Support\Facades\Storage::disk('local')->allFiles('remote-part-preorders'));
            }
        } catch (Throwable $e) {
            $n = -1;
        }
        if ($n < 0) {
            // fallback filesystem count for both common roots
            $n = 0;
            foreach ([$photoRoot, $photoRootPrivate] as $dir) {
                if (! is_dir($dir)) {
                    continue;
                }
                try {
                    $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS));
                    foreach ($it as $f) {
                        if ($f->isFile()) {
                            $n++;
                        }
                    }
                } catch (Throwable $e) {
                }
            }
        }
        if ($n > 0) {
            $details[] = '✓ فایل‌های عکس پیش‌سفارش قطعه: '.$n.' عدد';
        } elseif (is_dir($photoRoot) || is_dir($photoRootPrivate)) {
            $details[] = '· پوشه عکس پیش‌سفارش خالی است';
        } else {
            $details[] = '· هنوز پوشه عکس پیش‌سفارش ساخته نشده (اگر قبلاً ثبت شده باید بررسی شود)';
        }

        try {
            \Illuminate\Support\Facades\DB::connection()->getPdo();
            $details[] = '✓ اتصال دیتابیس برقرار است';
            if (\Illuminate\Support\Facades\Schema::hasTable('remote_part_preorders')) {
                $count = (int) \Illuminate\Support\Facades\DB::table('remote_part_preorders')->count();
                $details[] = '✓ جدول پیش‌سفارش قطعه: '.$count.' رکورد';
            }
            if (\Illuminate\Support\Facades\Schema::hasTable('customers')) {
                $count = (int) \Illuminate\Support\Facades\DB::table('customers')->count();
                $details[] = '✓ جدول مشتریان: '.$count.' رکورد';
            }
        } catch (Throwable $e) {
            $ok = false;
            $details[] = '✗ دیتابیس: '.$e->getMessage();
        }

        return ['ok' => $ok, 'details' => $details];
    }

    /**
     * Banner hint for admin layout (cached).
     *
     * @return array{has_update:bool,latest:?string,current:string}|null
     */
    public function bannerStatus(): ?array
    {
        if (! auth()->check()) {
            return null;
        }
        $user = auth()->user();
        if (! method_exists($user, 'canAccess') || ! $user->canAccess('system.tools')) {
            return null;
        }

        return Cache::remember($this->bannerCacheKey(), (int) config('updates.banner_cache_seconds', 120), function () {
            $c = $this->checkForUpdate(false);
            if (! ($c['ok'] ?? false)) {
                return ['has_update' => false, 'latest' => null, 'current' => $this->installedVersion()];
            }

            return [
                'has_update' => (bool) ($c['has_update'] ?? false),
                'latest' => $c['latest'] ?? null,
                'current' => $c['current'] ?? $this->installedVersion(),
            ];
        });
    }

    /**
     * Seller: read manifest from storage/app/releases/manifest.json
     *
     * @return array{ok:bool,latest:?string,release:?array,message?:string,file?:string,sha256?:string,changelog?:array,released_at?:string}
     */
    public function localLatest(string $channel = 'stable'): array
    {
        $manifestPath = storage_path('app/releases/manifest.json');
        if (! is_file($manifestPath)) {
            return ['ok' => false, 'latest' => null, 'release' => null, 'message' => 'مانیفست آپدیت روی سرور وجود ندارد.'];
        }

        $manifest = json_decode((string) file_get_contents($manifestPath), true);
        if (! is_array($manifest)) {
            return ['ok' => false, 'latest' => null, 'release' => null, 'message' => 'مانیفست نامعتبر است.'];
        }

        $latest = (string) ($manifest['latest'] ?? '');
        $releases = is_array($manifest['releases'] ?? null) ? $manifest['releases'] : [];
        $release = null;
        foreach ($releases as $row) {
            if (! is_array($row)) {
                continue;
            }
            if ((string) ($row['version'] ?? '') === $latest) {
                $release = $row;
                break;
            }
        }
        if ($release === null && $releases !== []) {
            $release = $releases[0];
            $latest = (string) ($release['version'] ?? $latest);
        }

        if ($latest === '' || $release === null) {
            return ['ok' => false, 'latest' => null, 'release' => null, 'message' => 'هیچ نسخه‌ای در مانیفست ثبت نشده.'];
        }

        return [
            'ok' => true,
            'latest' => $latest,
            'release' => $release,
            'file' => (string) ($release['file'] ?? ''),
            'sha256' => (string) ($release['sha256'] ?? ''),
            'changelog' => is_array($release['changelog'] ?? null)
                ? array_values(array_map('strval', $release['changelog']))
                : [],
            'released_at' => isset($release['released_at']) ? (string) $release['released_at'] : null,
            'channel' => (string) ($manifest['channel'] ?? $channel),
        ];
    }

    /**
     * Absolute path to a release ZIP on seller, after license-gated request.
     */
    public function sellerZipPath(string $version): ?string
    {
        $local = $this->localLatest();
        if (! ($local['ok'] ?? false)) {
            return null;
        }
        if ($version !== '' && $version !== (string) $local['latest']) {
            // Allow requesting a specific listed version
            $manifestPath = storage_path('app/releases/manifest.json');
            $manifest = json_decode((string) file_get_contents($manifestPath), true);
            $found = null;
            foreach (($manifest['releases'] ?? []) as $row) {
                if ((string) ($row['version'] ?? '') === $version) {
                    $found = $row;
                    break;
                }
            }
            if (! $found) {
                return null;
            }
            $file = (string) ($found['file'] ?? '');
        } else {
            $file = (string) ($local['file'] ?? '');
        }

        if ($file === '' || str_contains($file, '..') || str_contains($file, '/')) {
            return null;
        }

        $path = storage_path('app/releases/'.$file);

        return is_file($path) ? $path : null;
    }

    public function versionCompare(string $a, string $b): int
    {
        return version_compare($this->normalizeVersion($a), $this->normalizeVersion($b));
    }

    private function normalizeVersion(string $v): string
    {
        $v = trim($v);
        if (preg_match('/^v?(\d+(?:\.\d+){0,3})/', $v, $m)) {
            return $m[1];
        }

        return $v;
    }

    /**
     * @param  array{ok?:bool,latest?:?string,changelog?:array,released_at?:?string,message?:string}  $local
     * @return array{ok:bool,has_update:bool,current:string,latest:?string,changelog:array,released_at:?string,message:string,server:string,sha256?:?string}
     */
    private function compareResult(string $current, array $local, string $server, string $fallbackMsg): array
    {
        if (! ($local['ok'] ?? false)) {
            return [
                'ok' => true,
                'has_update' => false,
                'current' => $current,
                'latest' => null,
                'changelog' => [],
                'released_at' => null,
                'message' => $local['message'] ?? $fallbackMsg,
                'server' => $server,
            ];
        }

        $latest = (string) ($local['latest'] ?? '');
        $has = $latest !== '' && $this->versionCompare($latest, $current) > 0;

        return [
            'ok' => true,
            'has_update' => $has,
            'current' => $current,
            'latest' => $latest ?: null,
            'changelog' => $local['changelog'] ?? [],
            'released_at' => $local['released_at'] ?? null,
            'message' => $has ? ('نسخه جدید '.$latest.' آماده است.') : 'نرم‌افزار به‌روز است.',
            'server' => $server,
            'sha256' => $local['sha256'] ?? null,
        ];
    }

    /**
     * @return array{ok:bool,path?:string,sha256?:string,message?:string,details?:array<int,string>}
     */
    private function downloadFromServer(string $version): array
    {
        $server = rtrim((string) config('license.server', ''), '/');
        $key = trim((string) config('license.key'));
        $token = (string) config('license.token');
        $domain = \App\Models\ProductLicense::normalizeDomain(
            (string) (config('license.domain') ?: request()->getHost())
        );

        $tmpDir = storage_path('app/tmp');
        if (! is_dir($tmpDir)) {
            @mkdir($tmpDir, 0755, true);
        }
        $zipPath = $tmpDir.'/update_'.bin2hex(random_bytes(6)).'.zip';

        try {
            $response = Http::timeout(300)
                ->asForm()
                ->withOptions(['sink' => $zipPath])
                ->post($server.'/license/updates/download', [
                    'license_key' => $key,
                    'domain' => $domain,
                    'token' => $token,
                    'version' => $version,
                    'channel' => (string) config('updates.channel', 'stable'),
                    'product' => (string) config('updates.product', 'hddland-repair'),
                ]);

            if (! $response->successful() || ! is_file($zipPath) || filesize($zipPath) < 1000) {
                @unlink($zipPath);
                $json = $response->json();
                $msg = is_array($json) ? (string) ($json['message'] ?? '') : '';

                return [
                    'ok' => false,
                    'message' => $msg !== '' ? $msg : ('دانلود ناموفق (HTTP '.$response->status().').'),
                    'details' => [],
                ];
            }

            // If server returned JSON error into the sink file, detect it
            $head = (string) file_get_contents($zipPath, false, null, 0, 32);
            if (str_starts_with(ltrim($head), '{')) {
                $json = json_decode((string) file_get_contents($zipPath), true);
                @unlink($zipPath);

                return [
                    'ok' => false,
                    'message' => is_array($json) ? (string) ($json['message'] ?? 'خطای سرور در دانلود') : 'پاسخ نامعتبر',
                ];
            }

            return [
                'ok' => true,
                'path' => $zipPath,
                'sha256' => (string) ($response->header('X-Update-Sha256') ?: ''),
                'details' => ['✓ دانلود کامل شد ('.number_format(filesize($zipPath) / 1048576, 2).' مگابایت)'],
            ];
        } catch (Throwable $e) {
            @unlink($zipPath);

            return ['ok' => false, 'message' => 'دانلود: '.$e->getMessage()];
        }
    }

    /**
     * @return array{ok:bool,message:string,details:array<int,string>}
     */
    private function overlayZip(string $zipPath): array
    {
        $details = [];
        $tmpDir = storage_path('app/tmp/extract_'.bin2hex(random_bytes(4)));
        @mkdir($tmpDir, 0755, true);

        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            return ['ok' => false, 'message' => 'باز کردن ZIP ناموفق بود.', 'details' => $details];
        }
        $zip->extractTo($tmpDir);
        $zip->close();
        $details[] = '✓ استخراج ZIP';

        $src = $this->findAppRoot($tmpDir);
        if ($src === null) {
            $this->rrmdir($tmpDir);

            return [
                'ok' => false,
                'message' => 'ساختار بسته آپدیت نامعتبر است (artisan یا app/ یافت نشد).',
                'details' => $details,
            ];
        }
        $details[] = 'منبع: '.basename($src);

        $base = base_path();
        $copied = 0;
        $failed = 0;

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($src, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            /** @var \SplFileInfo $item */
            $rel = substr($item->getPathname(), strlen($src) + 1);
            $rel = str_replace('\\', '/', $rel);
            if ($rel === '' || $rel === false) {
                continue;
            }

            $baseName = basename($rel);
            if (! str_contains($rel, '/') && in_array($baseName, self::SKIP_ROOT_NAMES, true)) {
                continue;
            }

            $skip = false;
            foreach (self::SKIP_PREFIXES as $prefix) {
                if (str_starts_with($rel, $prefix)) {
                    $skip = true;
                    break;
                }
            }
            if ($skip) {
                continue;
            }
            // Never overwrite env-like files anywhere at project root patterns
            if (preg_match('/^\.env(\.|$)/', $baseName)) {
                continue;
            }
            // Never overwrite local DB dumps / demo sqlite under database/
            if (str_starts_with($rel, 'database/') && preg_match('/\.(sqlite|sql|sql\.gz)$/i', $baseName)) {
                continue;
            }

            $dest = $base.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $rel);
            if ($item->isDir()) {
                if (! is_dir($dest)) {
                    if (! @mkdir($dest, 0755, true)) {
                        $failed++;
                    }
                }
                continue;
            }

            $parent = dirname($dest);
            if (! is_dir($parent)) {
                @mkdir($parent, 0755, true);
            }
            if (@copy($item->getPathname(), $dest)) {
                $copied++;
            } else {
                $failed++;
            }
        }

        $this->rrmdir($tmpDir);
        $details[] = "✓ کپی فایل‌ها: {$copied} موفق".($failed ? "، {$failed} ناموفق" : '');

        return [
            'ok' => $failed === 0 && $copied > 0,
            'message' => $copied > 0
                ? 'فایل‌های آپدیت روی نصب کپی شدند.'
                : 'هیچ فایلی کپی نشد.',
            'details' => $details,
        ];
    }

    private function findAppRoot(string $extractDir): ?string
    {
        $candidates = [$extractDir];
        foreach (scandir($extractDir) ?: [] as $d) {
            if ($d === '.' || $d === '..') {
                continue;
            }
            $p = $extractDir.'/'.$d;
            if (is_dir($p)) {
                $candidates[] = $p;
                $nested = $p.'/support-hdd-land';
                if (is_dir($nested)) {
                    $candidates[] = $nested;
                }
            }
        }

        foreach ($candidates as $cand) {
            if (is_file($cand.'/artisan') && is_dir($cand.'/app')) {
                return $cand;
            }
        }

        return null;
    }

    private function bannerCacheKey(): string
    {
        return 'app_update_banner_'.sha1($this->installedVersion().'|'.(string) config('license.server'));
    }

    private function rrmdir(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }
        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($it as $f) {
            $f->isDir() ? @rmdir($f->getPathname()) : @unlink($f->getPathname());
        }
        @rmdir($dir);
    }
}
