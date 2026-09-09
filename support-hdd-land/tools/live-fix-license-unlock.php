<?php
/**
 * One-shot unlock for customer hosts locked by missing seller /license/verify.
 * Upload ONLY this file into the site document root (public/), open it once, then DELETE it.
 *
 * Example: https://support.hddsoftware.ir/_license_unlock.php
 */
header('Content-Type: text/plain; charset=utf-8');

$public = __DIR__;
$root = is_file($public.'/../artisan') ? dirname($public) : (is_file($public.'/artisan') ? $public : null);
if ($root === null) {
    echo "ERROR: cannot find Laravel root (artisan). Put this file in public/\n";
    exit(1);
}
echo "root={$root}\n";

$mw = <<<'MW'
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * License gate for customer installs.
 *
 * - Seller host (empty LICENSE_KEY) is never blocked.
 * - If this host IS the configured LICENSE_SERVER, skip remote verify
 *   (vendor panel must stay up even when issuing licenses).
 * - Explicit ok:false from seller API → hard block (revoked/expired/domain).
 * - Server down / route missing / non-license JSON → soft-fail (keep app up).
 */
class EnsureLicensed
{
    public function handle(Request $request, Closure $next): Response
    {
        $key = trim((string) config('license.key'));
        if ($key === '') {
            return $next($request);
        }

        // Installer + license API must always be reachable.
        if ($request->is('install.php') || $request->is('install') || $request->is('license/*')) {
            return $next($request);
        }

        // This machine is the license issuer — do not lock the vendor panel on itself.
        if ($this->isLicenseServerHost($request)) {
            return $next($request);
        }

        $domain = \App\Models\ProductLicense::normalizeDomain($request->getHost());
        $configured = \App\Models\ProductLicense::normalizeDomain((string) config('license.domain'));
        $token = trim((string) config('license.token'));
        $purchaseUrl = (string) config('license.purchase_url', 'https://hdd-land.ir');

        if ($configured !== '' && $configured !== $domain) {
            return response()->view('errors.license', [
                'message' => 'لایسنس این نصب برای دامنه دیگری ثبت شده است'
                    .($configured ? ' ('.$configured.')' : '')
                    .' و روی این هاست بلاک شده است.',
                'reason' => 'domain_mismatch',
                'purchase_url' => $purchaseUrl,
            ], 403);
        }

        if ($token === '') {
            return response()->view('errors.license', [
                'message' => 'لایسنس نصب نشده است. فایل install.php را اجرا کنید یا با فروشنده تماس بگیرید.',
                'reason' => 'inactive',
                'purchase_url' => $purchaseUrl,
            ], 403);
        }

        $check = $this->verifyWithServer($key, $domain, $token, $purchaseUrl);
        if (($check['block'] ?? false) === true) {
            return response()->view('errors.license', [
                'message' => (string) ($check['message'] ?? 'لایسنس معتبر نیست. برای تمدید با فروشنده تماس بگیرید.'),
                'reason' => (string) ($check['reason'] ?? 'inactive'),
                'purchase_url' => (string) ($check['purchase_url'] ?? $purchaseUrl),
            ], 403);
        }

        return $next($request);
    }

    protected function isLicenseServerHost(Request $request): bool
    {
        $server = rtrim((string) config('license.server', ''), '/');
        if ($server === '') {
            return false;
        }

        $serverHost = parse_url($server, PHP_URL_HOST);
        if (! is_string($serverHost) || $serverHost === '') {
            return false;
        }

        $current = \App\Models\ProductLicense::normalizeDomain($request->getHost());
        $seller = \App\Models\ProductLicense::normalizeDomain($serverHost);

        return $current !== '' && $current === $seller;
    }

    /**
     * @return array{block:bool,message?:string,reason?:string,purchase_url?:string}
     */
    private function verifyWithServer(string $key, string $domain, string $token, string $purchaseUrl): array
    {
        // v3: invalidate old caches that wrongly stored seller 404 as hard-block.
        $cacheKey = 'license_verify_v3_'.sha1($key.'|'.$domain.'|'.$token);

        $cached = Cache::get($cacheKey);
        if (is_array($cached) && array_key_exists('block', $cached)) {
            return $cached;
        }

        $server = rtrim((string) config('license.server', 'https://support.hdd-land.ir'), '/');
        if ($server === '') {
            return ['block' => false];
        }

        try {
            $response = Http::timeout(8)
                ->asForm()
                ->acceptJson()
                ->post($server.'/license/verify', [
                    'license_key' => $key,
                    'domain' => $domain,
                    'token' => $token,
                    'version' => '1.0.0',
                ]);

            $status = $response->status();
            $json = $response->json();

            // Seller API missing / gateway errors must NOT lock every customer shop.
            if ($this->isInfrastructureFailure($status, $json, $response->body())) {
                Log::warning('license verify infrastructure failure', [
                    'server' => $server,
                    'status' => $status,
                    'body' => mb_substr((string) $response->body(), 0, 240),
                ]);

                return $this->softFail($cacheKey);
            }

            $ok = is_array($json) && array_key_exists('ok', $json) && ($json['ok'] === true || $json['ok'] === 1 || $json['ok'] === '1');

            if ($ok) {
                $result = ['block' => false, 'message' => (string) ($json['message'] ?? 'معتبر')];
                Cache::put($cacheKey, $result, now()->addHours(2));
                Cache::forget($cacheKey.'_last_block');
                try {
                    \App\Support\LicenseStatus::store([
                        'ok' => true,
                        'message' => $result['message'],
                        'plan' => $json['plan'] ?? null,
                        'plan_code' => $json['plan_code'] ?? null,
                        'plan_months' => $json['plan_months'] ?? null,
                        'price_toman' => $json['price_toman'] ?? null,
                        'activated_at' => $json['activated_at'] ?? null,
                        'expires_at' => $json['expires_at'] ?? null,
                    ]);
                } catch (\Throwable) {
                }

                return $result;
            }

            // Explicit denial from seller API.
            if (is_array($json) && array_key_exists('ok', $json) && ($json['ok'] === false || $json['ok'] === 0 || $json['ok'] === '0')) {
                $message = (string) ($json['message'] ?? 'لایسنس معتبر نیست.');
                $reason = (string) ($json['reason'] ?? 'inactive');
                $url = ! empty($json['purchase_url']) ? (string) $json['purchase_url'] : $purchaseUrl;

                $result = [
                    'block' => true,
                    'message' => $message,
                    'reason' => $reason,
                    'purchase_url' => $url,
                ];
                Cache::put($cacheKey, $result, now()->addMinutes(10));
                Cache::put($cacheKey.'_last_block', $result, now()->addDays(7));

                return $result;
            }

            // Unexpected payload → soft-fail.
            return $this->softFail($cacheKey);
        } catch (\Throwable $e) {
            Log::debug('license verify failed: '.$e->getMessage());

            return $this->softFail($cacheKey);
        }
    }

    /**
     * @param  mixed  $json
     */
    private function isInfrastructureFailure(int $status, $json, string $rawBody): bool
    {
        if (in_array($status, [404, 405, 501, 502, 503, 504], true)) {
            return true;
        }

        $message = '';
        if (is_array($json)) {
            $message = (string) ($json['message'] ?? $json['error'] ?? '');
        }
        if ($message === '') {
            $message = $rawBody;
        }

        $messageLower = mb_strtolower($message);

        return str_contains($messageLower, 'could not be found')
            || str_contains($messageLower, 'route [')
            || str_contains($messageLower, 'route license/verify')
            || (str_contains($messageLower, 'not found') && str_contains($messageLower, 'route'));
    }

    /**
     * @return array{block:bool,message?:string}
     */
    private function softFail(string $cacheKey): array
    {
        $lastBlock = Cache::get($cacheKey.'_last_block');
        if (is_array($lastBlock) && ($lastBlock['block'] ?? false)) {
            $msg = mb_strtolower((string) ($lastBlock['message'] ?? ''));
            // Never re-apply a stale "route missing / server down" as a hard lock.
            if (str_contains($msg, 'could not be found')
                || str_contains($msg, 'route license')
                || str_contains($msg, 'not found')) {
                Cache::forget($cacheKey.'_last_block');
            } else {
                return $lastBlock;
            }
        }

        return ['block' => false, 'message' => 'offline-soft'];
    }
}

MW;
$view = <<<'VIEW'
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>فعال‌سازی لایسنس</title>
    <style>
        body{margin:0;min-height:100vh;display:grid;place-items:center;font-family:Tahoma,sans-serif;background:#eef1f5;color:#1f2933}
        .box{background:#fff;border:1px solid #c9d0da;border-radius:10px;padding:28px;max-width:440px;text-align:center;box-shadow:0 10px 30px rgba(15,23,42,.06)}
        h1{margin:0 0 8px;font-size:20px}
        p{color:#667788;line-height:1.7;margin:0 0 12px}
        a.btn{display:inline-block;margin-top:6px;padding:.65rem 1rem;border-radius:10px;background:#1d4f91;color:#fff;text-decoration:none}
        a.link{color:#1d4f91}
        .reason{font-size:12px;color:#94a3b8;margin-top:10px}
    </style>
</head>
<body>
<div class="box">
    <h1>فعال‌سازی لازم است</h1>
    <p>{{ $message ?? 'لایسنس این نصب معتبر نیست یا منقضی شده است.' }}</p>
    @if(!empty($purchase_url))
        <p><a class="btn" href="{{ $purchase_url }}" rel="noopener">خرید / تمدید لایسنس</a></p>
    @endif
    <p><a class="link" href="{{ url('/') }}">بازگشت به صفحه اصلی</a></p>
    @if(!empty($reason))
        <div class="reason">کد: {{ $reason }}</div>
    @endif
</div>
</body>
</html>

VIEW;
$install = <<<'INST'
<?php
header('Location: /', true, 302);
exit;

INST;

$mwDst = $root.'/app/Http/Middleware/EnsureLicensed.php';
$viewDst = $root.'/resources/views/errors/license.blade.php';
$installDst = is_dir($root.'/public') ? $root.'/public/install.php' : $public.'/install.php';

$ok = true;
if (! is_dir(dirname($mwDst))) {
    echo "ERROR: missing app/Http/Middleware\n";
    $ok = false;
} else {
    // backup once
    if (is_file($mwDst) && ! is_file($mwDst.'.bak-license-unlock')) {
        @copy($mwDst, $mwDst.'.bak-license-unlock');
    }
    if (@file_put_contents($mwDst, $mw) !== false) {
        echo "middleware_patched=1\n";
    } else {
        echo "middleware_patch_failed\n";
        $ok = false;
    }
}

if (is_dir(dirname($viewDst))) {
    if (is_file($viewDst) && ! is_file($viewDst.'.bak-license-unlock')) {
        @copy($viewDst, $viewDst.'.bak-license-unlock');
    }
    @file_put_contents($viewDst, $view);
    echo "license_view_patched=1\n";
}

@file_put_contents($installDst, $install);
echo "install_stub=1 path={$installDst}\n";

if (is_file($root.'/vendor/autoload.php') && is_file($root.'/bootstrap/app.php')) {
    try {
        require $root.'/vendor/autoload.php';
        $app = require $root.'/bootstrap/app.php';
        $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
        $kernel->bootstrap();
        Illuminate\Support\Facades\Artisan::call('optimize:clear');
        echo "optimize_clear_ok\n";
        try {
            Illuminate\Support\Facades\Cache::flush();
            echo "cache_flush_ok\n";
        } catch (Throwable $e) {
            echo "cache_flush_skip=".$e->getMessage()."\n";
        }
    } catch (Throwable $e) {
        echo "bootstrap_fail=".$e->getMessage()."\n";
        $ok = false;
    }
} else {
    echo "laravel_bootstrap_missing (files patched; clear cache manually)\n";
}

echo $ok ? "DONE_UNLOCK_OK\n" : "DONE_WITH_ERRORS\n";
echo "DELETE this file now.\n";
