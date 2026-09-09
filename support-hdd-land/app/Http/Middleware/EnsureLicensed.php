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
        $cacheKey = 'license_verify_'.sha1($key.'|'.$domain.'|'.$token);

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
            return $lastBlock;
        }

        return ['block' => false, 'message' => 'offline-soft'];
    }
}
