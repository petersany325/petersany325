<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;
use ZipArchive;

/**
 * Customer-side live update: check seller license server, download ZIP, overlay files, migrate.
 * Never touches .env, storage/, vendor/, or node_modules/.
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

    /**
     * Validate customer license for update channel and build a user-facing report.
     *
     * @return array{
     *   valid:bool,
     *   code:string,
     *   label:string,
     *   message:string,
     *   can_update:bool,
     *   domain:?string,
     *   plan:?string,
     *   expires_at:?string,
     *   expires_jalali:?string,
     *   server:?string,
     *   checked_at:string
     * }
     */
    public function evaluateLicenseStatus(bool $probeServer = true): array
    {
        $server = rtrim((string) config('license.server', ''), '/');
        $key = trim((string) config('license.key'));
        $token = trim((string) config('license.token'));
        $configuredDomain = \App\Models\ProductLicense::normalizeDomain((string) config('license.domain'));
        $hostDomain = \App\Models\ProductLicense::normalizeDomain((string) request()->getHost());
        $checkedAt = now()->toIso8601String();

        // Seller panel (no LICENSE_KEY): always allowed to check/publish locally.
        if ($key === '') {
            return [
                'valid' => true,
                'code' => 'seller',
                'label' => 'سرور فروشنده',
                'message' => 'این نصب در حالت فروشنده است؛ بررسی لایسنس مشتری لازم نیست.',
                'can_update' => true,
                'domain' => $hostDomain ?: null,
                'plan' => (string) (config('license.plan') ?: 'فروشنده'),
                'expires_at' => null,
                'expires_jalali' => null,
                'server' => $server !== '' ? $server : null,
                'checked_at' => $checkedAt,
            ];
        }

        if ($token === '') {
            return [
                'valid' => false,
                'code' => 'missing_token',
                'label' => 'لایسنس ناقص',
                'message' => 'توکن لایسنس در تنظیمات نیست. نصب/فعال‌سازی را دوباره انجام دهید.',
                'can_update' => false,
                'domain' => $configuredDomain ?: $hostDomain ?: null,
                'plan' => (string) (config('license.plan') ?: null),
                'expires_at' => (string) (config('license.expires_at') ?: null) ?: null,
                'expires_jalali' => null,
                'server' => $server !== '' ? $server : null,
                'checked_at' => $checkedAt,
            ];
        }

        if ($configuredDomain !== '' && $hostDomain !== '' && $configuredDomain !== $hostDomain) {
            return [
                'valid' => false,
                'code' => 'domain_mismatch',
                'label' => 'دامنه نامعتبر',
                'message' => 'لایسنس برای دامنه «'.$configuredDomain.'» صادر شده و روی «'.$hostDomain.'» معتبر نیست.',
                'can_update' => false,
                'domain' => $configuredDomain,
                'plan' => (string) (config('license.plan') ?: null),
                'expires_at' => (string) (config('license.expires_at') ?: null) ?: null,
                'expires_jalali' => null,
                'server' => $server !== '' ? $server : null,
                'checked_at' => $checkedAt,
            ];
        }

        $localExpiry = trim((string) config('license.expires_at'));
        if ($localExpiry !== '') {
            try {
                if (now()->greaterThan(\Illuminate\Support\Carbon::parse($localExpiry)->endOfDay())) {
                    return [
                        'valid' => false,
                        'code' => 'expired',
                        'label' => 'منقضی شده',
                        'message' => 'اعتبار لایسنس به پایان رسیده است ('.$localExpiry.'). برای تمدید با فروشنده تماس بگیرید.',
                        'can_update' => false,
                        'domain' => $configuredDomain ?: $hostDomain ?: null,
                        'plan' => (string) (config('license.plan') ?: null),
                        'expires_at' => $localExpiry,
                        'expires_jalali' => function_exists('jalali_like') ? jalali_like(\Illuminate\Support\Carbon::parse($localExpiry)) : null,
                        'server' => $server !== '' ? $server : null,
                        'checked_at' => $checkedAt,
                    ];
                }
            } catch (\Throwable) {
                // ignore parse errors; remote probe will decide
            }
        }

        if (! $probeServer || $server === '') {
            return [
                'valid' => true,
                'code' => 'local_ok',
                'label' => 'معتبر (محلی)',
                'message' => 'لایسنس محلی سالم است'.($server === '' ? '؛ آدرس سرور لایسنس تنظیم نشده.' : '؛ بدون استعلام آنلاین.'),
                'can_update' => $server !== '',
                'domain' => $configuredDomain ?: $hostDomain ?: null,
                'plan' => (string) (config('license.plan') ?: null),
                'expires_at' => $localExpiry !== '' ? $localExpiry : null,
                'expires_jalali' => null,
                'server' => $server !== '' ? $server : null,
                'checked_at' => $checkedAt,
            ];
        }

        try {
            $response = \Illuminate\Support\Facades\Http::timeout(12)
                ->asForm()
                ->acceptJson()
                ->post($server.'/license/verify', [
                    'license_key' => $key,
                    'domain' => $configuredDomain ?: $hostDomain,
                    'token' => $token,
                    'version' => $this->installedVersion(),
                ]);

            $status = $response->status();
            $json = $response->json();

            // Seller route missing / gateway down → soft report, do not pretend valid for paid updates.
            if (in_array($status, [404, 405, 501, 502, 503, 504], true)
                || (is_array($json) && is_string($json['message'] ?? null)
                    && str_contains(mb_strtolower((string) $json['message']), 'could not be found'))) {
                return [
                    'valid' => false,
                    'code' => 'server_unreachable',
                    'label' => 'سرور لایسنس در دسترس نیست',
                    'message' => 'سرور لایسنس مسیر تأیید را پاسخ نداد (HTTP '.$status.'). تا رفع مشکل فروشنده، آپدیت قفل است.',
                    'can_update' => false,
                    'domain' => $configuredDomain ?: $hostDomain ?: null,
                    'plan' => (string) (config('license.plan') ?: null),
                    'expires_at' => $localExpiry !== '' ? $localExpiry : null,
                    'expires_jalali' => null,
                    'server' => $server,
                    'checked_at' => $checkedAt,
                ];
            }

            if (is_array($json) && array_key_exists('ok', $json) && ($json['ok'] === true || $json['ok'] === 1 || $json['ok'] === '1')) {
                $expires = isset($json['expires_at']) ? (string) $json['expires_at'] : ($localExpiry !== '' ? $localExpiry : null);
                $plan = isset($json['plan']) ? (string) $json['plan'] : (string) (config('license.plan') ?: null);

                return [
                    'valid' => true,
                    'code' => 'valid',
                    'label' => 'معتبر',
                    'message' => (string) ($json['message'] ?? 'لایسنس معتبر است و امکان دریافت آپدیت وجود دارد.'),
                    'can_update' => true,
                    'domain' => $configuredDomain ?: $hostDomain ?: null,
                    'plan' => $plan ?: null,
                    'expires_at' => $expires,
                    'expires_jalali' => ($expires && function_exists('jalali_like')) ? jalali_like(\Illuminate\Support\Carbon::parse($expires)) : null,
                    'server' => $server,
                    'checked_at' => $checkedAt,
                ];
            }

            if (is_array($json) && array_key_exists('ok', $json) && ($json['ok'] === false || $json['ok'] === 0 || $json['ok'] === '0')) {
                $msg = (string) ($json['message'] ?? 'لایسنس معتبر نیست.');
                $reason = (string) ($json['reason'] ?? 'invalid');
                $code = match (true) {
                    str_contains($msg, 'گذشته') || str_contains($msg, 'منقضی') || $reason === 'expired' => 'expired',
                    str_contains($msg, 'باطل') || $reason === 'revoked' => 'revoked',
                    str_contains($msg, 'دامنه') || $reason === 'domain_mismatch' => 'domain_mismatch',
                    default => 'invalid',
                };
                $labels = [
                    'expired' => 'منقضی شده',
                    'revoked' => 'باطل شده',
                    'domain_mismatch' => 'دامنه نامعتبر',
                    'invalid' => 'نامعتبر',
                ];

                return [
                    'valid' => false,
                    'code' => $code,
                    'label' => $labels[$code] ?? 'نامعتبر',
                    'message' => $msg.' — تا اعتبارسنجی موفق، آپدیت در دسترس نیست.',
                    'can_update' => false,
                    'domain' => $configuredDomain ?: $hostDomain ?: null,
                    'plan' => isset($json['plan']) ? (string) $json['plan'] : (string) (config('license.plan') ?: null),
                    'expires_at' => isset($json['expires_at']) ? (string) $json['expires_at'] : ($localExpiry !== '' ? $localExpiry : null),
                    'expires_jalali' => null,
                    'server' => $server,
                    'checked_at' => $checkedAt,
                ];
            }

            return [
                'valid' => false,
                'code' => 'server_unreachable',
                'label' => 'پاسخ نامعتبر سرور',
                'message' => 'پاسخ سرور لایسنس قابل تشخیص نبود. آپدیت تا تأیید مجدد قفل است.',
                'can_update' => false,
                'domain' => $configuredDomain ?: $hostDomain ?: null,
                'plan' => (string) (config('license.plan') ?: null),
                'expires_at' => $localExpiry !== '' ? $localExpiry : null,
                'expires_jalali' => null,
                'server' => $server,
                'checked_at' => $checkedAt,
            ];
        } catch (\Throwable $e) {
            return [
                'valid' => false,
                'code' => 'server_unreachable',
                'label' => 'خطا در ارتباط',
                'message' => 'ارتباط با سرور لایسنس برقرار نشد: '.$e->getMessage(),
                'can_update' => false,
                'domain' => $configuredDomain ?: $hostDomain ?: null,
                'plan' => (string) (config('license.plan') ?: null),
                'expires_at' => $localExpiry !== '' ? $localExpiry : null,
                'expires_jalali' => null,
                'server' => $server,
                'checked_at' => $checkedAt,
            ];
        }
    }

    public function checkForUpdate(bool $force = false): array
    {
        $current = $this->installedVersion();
        $channel = (string) config('updates.channel', 'stable');
        $server = rtrim((string) config('license.server', ''), '/');
        $license = $this->evaluateLicenseStatus(true);

        if ($server === '') {
            return [
                'ok' => false,
                'has_update' => false,
                'current' => $current,
                'latest' => null,
                'changelog' => [],
                'released_at' => null,
                'message' => 'آدرس سرور لایسنس تنظیم نشده است.',
                'license' => $license,
            ];
        }

        // Customer with invalid/expired license: report clearly and block update channel.
        if (! ($license['can_update'] ?? false) && ($license['code'] ?? '') !== 'seller') {
            return [
                'ok' => false,
                'has_update' => false,
                'current' => $current,
                'latest' => null,
                'changelog' => [],
                'released_at' => null,
                'message' => 'لایسنس: '.$license['label'].' — '.$license['message'],
                'license' => $license,
                'server' => $server,
            ];
        }

        $cacheKey = 'app_update_check_'.sha1($server.'|'.$channel.'|'.$current);
        if (! $force) {
            $cached = Cache::get($cacheKey);
            if (is_array($cached)) {
                $cached['license'] = $license;

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
            $result['license'] = $license;
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

                // Prefer structured license report from seller when auth failed.
                if (is_array($json['license'] ?? null)) {
                    $license = array_merge($license, $json['license']);
                    $license['can_update'] = false;
                    $license['valid'] = false;
                    $msg = 'لایسنس: '.($license['label'] ?? 'نامعتبر').' — '.($license['message'] ?? $msg);
                }

                return [
                    'ok' => false,
                    'has_update' => false,
                    'current' => $current,
                    'latest' => null,
                    'changelog' => [],
                    'released_at' => null,
                    'message' => $msg,
                    'server' => $server,
                    'license' => $license,
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
                'license' => $license,
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
                'license' => $license,
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
        $license = $this->evaluateLicenseStatus(true);
        $details[] = 'وضعیت لایسنس: '.($license['label'] ?? '—').' ('.($license['code'] ?? '?').')';
        if (! ($license['can_update'] ?? false)) {
            return [
                'ok' => false,
                'message' => 'نصب آپدیت ممکن نیست — '.$license['message'],
                'details' => $details,
                'license' => $license,
            ];
        }

        $check = $this->checkForUpdate(true);
        if (! ($check['ok'] ?? false)) {
            return [
                'ok' => false,
                'message' => $check['message'] ?? 'بررسی آپدیت ناموفق.',
                'details' => $details,
                'license' => $check['license'] ?? $license,
            ];
        }
        if (! ($check['has_update'] ?? false)) {
            return [
                'ok' => true,
                'message' => 'نسخه جدیدتری وجود ندارد.',
                'details' => $details,
                'version' => $check['current'],
                'license' => $check['license'] ?? $license,
            ];
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
                    return [
                        'ok' => false,
                        'message' => 'فایل ZIP آپدیت در مخزن محلی یافت نشد.',
                        'details' => $details,
                        'license' => $license,
                    ];
                }
                $expectedSha = (string) ($local['sha256'] ?? '');
            } else {
                $download = $this->downloadFromServer($targetVersion);
                if (! ($download['ok'] ?? false)) {
                    return [
                        'ok' => false,
                        'message' => $download['message'] ?? 'دانلود آپدیت ناموفق.',
                        'details' => array_merge($details, $download['details'] ?? []),
                        'license' => $license,
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
                        'license' => $license,
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
                    'license' => $license,
                ];
            }

            $migrate = $this->maintenance->runPendingMigrations();
            $details = array_merge($details, ['— مایگریشن —'], $migrate['details'] ?? []);

            $cache = $this->maintenance->clearCaches();
            $details = array_merge($details, ['— پاک‌سازی کش —'], $cache['details'] ?? []);

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
                'license' => $license,
            ];
        } catch (Throwable $e) {
            Log::error('app_update_apply_failed', ['error' => $e->getMessage()]);

            return [
                'ok' => false,
                'message' => 'نصب آپدیت متوقف شد: '.$e->getMessage(),
                'details' => $details,
                'license' => $license,
            ];
        } finally {
            if ($zipPath && str_contains($zipPath, DIRECTORY_SEPARATOR.'tmp'.DIRECTORY_SEPARATOR)
                && is_file($zipPath)) {
                @unlink($zipPath);
            }
        }
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
            $license = is_array($c['license'] ?? null) ? $c['license'] : null;
            if (! ($c['ok'] ?? false)) {
                return [
                    'has_update' => false,
                    'latest' => null,
                    'current' => $c['current'] ?? $this->installedVersion(),
                    'license_locked' => $license && empty($license['can_update']),
                    'license' => $license,
                    'message' => $c['message'] ?? null,
                ];
            }

            return [
                'has_update' => (bool) ($c['has_update'] ?? false),
                'latest' => $c['latest'] ?? null,
                'current' => $c['current'] ?? $this->installedVersion(),
                'license_locked' => false,
                'license' => $license,
                'message' => $c['message'] ?? null,
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
