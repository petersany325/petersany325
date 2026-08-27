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
 * Seller site (no LICENSE_KEY) is not blocked.
 * Periodically verifies with seller server so revoke/expiry/renewal is enforced.
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

        $check = $this->verifyWithServer($key, $domain, $token);
        if (($check['block'] ?? false) === true) {
            return response()->view('errors.license', [
                'message' => (string) ($check['message'] ?? 'لایسنس معتبر نیست. برای تمدید با فروشنده تماس بگیرید.'),
            ], 403);
        }

        return $next($request);
    }

    /**
     * @return array{block:bool,message?:string}
     */
    private function verifyWithServer(string $key, string $domain, string $token): array
    {
        $cacheKey = 'license_verify_'.sha1($key.'|'.$domain.'|'.$token);

        // Cache positive result briefly; negative/block results shorter so renew takes effect sooner.
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

            $json = $response->json();
            $ok = is_array($json) && ($json['ok'] ?? false) === true;

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

            $message = is_array($json)
                ? (string) ($json['message'] ?? 'لایسنس معتبر نیست.')
                : 'ارتباط با سرور لایسنس نامعتبر بود.';

            // Revoked / expired / invalid → block; short cache so seller renew unlocks soon
            $result = ['block' => true, 'message' => $message];
            Cache::put($cacheKey, $result, now()->addMinutes(10));
            Cache::put($cacheKey.'_last_block', $result, now()->addDays(7));

            return $result;
        } catch (\Throwable $e) {
            Log::debug('license verify failed: '.$e->getMessage());

            // Soft-fail offline: keep shop up unless we recently knew license was blocked.
            $lastBlock = Cache::get($cacheKey.'_last_block');
            if (is_array($lastBlock) && ($lastBlock['block'] ?? false)) {
                return $lastBlock;
            }

            return ['block' => false, 'message' => 'offline-soft'];
        }
    }
}
