<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Soft license gate for customer installs.
 * Seller's own site (no LICENSE_KEY) is not blocked.
 * Periodically heartbeats verify() so seller can see online status.
 */
class EnsureLicensed
{
    public function handle(Request $request, Closure $next): Response
    {
        $key = trim((string) config('license.key'));
        if ($key === '') {
            return $next($request);
        }

        // Allow installer + license API always
        if ($request->is('install.php') || $request->is('install') || $request->is('license/*')) {
            return $next($request);
        }

        $domain = \App\Models\ProductLicense::normalizeDomain($request->getHost());
        $configured = \App\Models\ProductLicense::normalizeDomain((string) config('license.domain'));
        $token = (string) config('license.token');

        if ($configured !== '' && $configured !== $domain) {
            return response()->view('errors.license', [
                'message' => 'لایسنس این نصب برای دامنه دیگری ثبت شده است.',
            ], 403);
        }

        if ($key === '' || $token === '') {
            return response()->view('errors.license', [
                'message' => 'لایسنس نصب نشده است. فایل install.php را اجرا کنید.',
            ], 403);
        }

        $this->heartbeat($key, $domain, $token);

        return $next($request);
    }

    private function heartbeat(string $key, string $domain, string $token): void
    {
        $cacheKey = 'license_heartbeat_'.sha1($key.'|'.$domain);
        if (Cache::get($cacheKey)) {
            return;
        }

        // At most once per 12 hours per install
        Cache::put($cacheKey, 1, now()->addHours(12));

        $server = rtrim((string) config('license.server', 'https://support.hdd-land.ir'), '/');
        if ($server === '') {
            return;
        }

        try {
            Http::timeout(6)
                ->asForm()
                ->acceptJson()
                ->post($server.'/license/verify', [
                    'license_key' => $key,
                    'domain' => $domain,
                    'token' => $token,
                    'version' => '1.0.0',
                ]);
        } catch (\Throwable $e) {
            Log::debug('license heartbeat failed: '.$e->getMessage());
        }
    }
}
