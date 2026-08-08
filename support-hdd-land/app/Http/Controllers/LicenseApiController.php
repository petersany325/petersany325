<?php

namespace App\Http\Controllers;

use App\Models\ProductLicense;
use App\Services\NiazpardazSmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Seller-side license activation API used by the web installer on customer hosts.
 */
class LicenseApiController extends Controller
{
    /** Step 1: validate serial + send SMS code to license phone. */
    public function requestOtp(Request $request, NiazpardazSmsService $sms)
    {
        $data = $request->validate([
            'license_key' => ['required', 'string', 'max:64'],
            'domain' => ['required', 'string', 'max:190'],
            'phone' => ['nullable', 'string', 'max:30'],
            'product' => ['nullable', 'string', 'max:60'],
        ]);

        $key = ProductLicense::normalizeKey($data['license_key']);
        $domain = ProductLicense::normalizeDomain($data['domain']);
        $product = $data['product'] ?? 'hddland-repair';

        $check = $this->precheckLicense($key, $domain, $product);
        if (! ($check['ok'] ?? false)) {
            return response()->json(['ok' => false, 'message' => $check['message']], $check['http'] ?? 422);
        }

        /** @var ProductLicense $license */
        $license = $check['license'];
        $phone = preg_replace('/\D+/', '', (string) ($data['phone'] ?: $license->customer_phone)) ?: '';
        if (strlen($phone) < 10) {
            return response()->json([
                'ok' => false,
                'message' => 'برای این سریال موبایل ثبت نشده. موبایل خریدار را وارد کنید یا در پنل فروشنده روی لایسنس موبایل بگذارید.',
            ], 422);
        }

        // Keep phone on license for future renewals
        if (! $license->customer_phone) {
            $license->forceFill(['customer_phone' => $phone])->save();
        }

        $code = (string) random_int(10000, 99999);
        $cacheKey = $this->otpCacheKey($key, $domain);
        Cache::put($cacheKey, [
            'code' => $code,
            'phone' => $phone,
            'domain' => $domain,
            'attempts' => 0,
        ], now()->addMinutes(10));

        $shop = shop_name();
        $message = "{$shop}\nکد تأیید نصب لایسنس:\n{$code}\nدامنه: {$domain}\nاعتبار ۱۰ دقیقه";
        $sent = $sms->send($phone, $message, $code);
        if (! ($sent['ok'] ?? false)) {
            return response()->json([
                'ok' => false,
                'message' => 'ارسال پیامک ناموفق بود: '.($sent['message'] ?? 'خطای SMS'),
            ], 502);
        }

        return response()->json([
            'ok' => true,
            'message' => 'کد تأیید پیامک شد.',
            'phone_masked' => $this->maskPhone($phone),
            'expires_in' => 600,
            'purchase_url' => config('license.purchase_url', 'https://hdd-land.ir'),
        ]);
    }

    /** Step 2: confirm SMS code → activate license for domain. */
    public function confirmOtp(Request $request)
    {
        $data = $request->validate([
            'license_key' => ['required', 'string', 'max:64'],
            'domain' => ['required', 'string', 'max:190'],
            'phone' => ['nullable', 'string', 'max:30'],
            'code' => ['required', 'string', 'max:12'],
            'product' => ['nullable', 'string', 'max:60'],
            'version' => ['nullable', 'string', 'max:30'],
        ]);

        $key = ProductLicense::normalizeKey($data['license_key']);
        $domain = ProductLicense::normalizeDomain($data['domain']);
        $phone = preg_replace('/\D+/', '', (string) $data['phone']) ?: '';
        $code = trim((string) $data['code']);
        $product = $data['product'] ?? 'hddland-repair';

        $cacheKey = $this->otpCacheKey($key, $domain);
        $payload = Cache::get($cacheKey);
        if (! is_array($payload) || empty($payload['code'])) {
            return response()->json(['ok' => false, 'message' => 'کد منقضی شده. دوباره درخواست پیامک بدهید.'], 410);
        }

        $cachedPhone = preg_replace('/\D+/', '', (string) ($payload['phone'] ?? '')) ?: '';
        if ($phone !== '' && $cachedPhone !== '' && $phone !== $cachedPhone) {
            return response()->json(['ok' => false, 'message' => 'شماره موبایل با درخواست پیامک هم‌خوان نیست.'], 422);
        }
        if ($phone === '') {
            $phone = $cachedPhone;
        }

        $attempts = (int) ($payload['attempts'] ?? 0) + 1;
        $payload['attempts'] = $attempts;
        Cache::put($cacheKey, $payload, now()->addMinutes(10));
        if ($attempts > 8) {
            Cache::forget($cacheKey);

            return response()->json(['ok' => false, 'message' => 'تعداد تلاش زیاد شد. دوباره کد بگیرید.'], 429);
        }

        if (! hash_equals((string) $payload['code'], $code)) {
            return response()->json(['ok' => false, 'message' => 'کد تأیید نادرست است.'], 422);
        }

        Cache::forget($cacheKey);

        $result = $this->performActivation($key, $domain, $product, $data['version'] ?? null, $request->ip());
        if (! ($result['ok'] ?? false)) {
            return response()->json(['ok' => false, 'message' => $result['message']], $result['http'] ?? 422);
        }

        return response()->json($result);
    }

    /**
     * Legacy activate — only allowed after OTP proof (or demo path on installer).
     * Prefer confirm-otp for customer installs.
     */
    public function activate(Request $request)
    {
        $data = $request->validate([
            'license_key' => ['required', 'string', 'max:64'],
            'domain' => ['required', 'string', 'max:190'],
            'product' => ['nullable', 'string', 'max:60'],
            'version' => ['nullable', 'string', 'max:30'],
            'otp_proof' => ['nullable', 'string', 'max:64'],
        ]);

        $key = ProductLicense::normalizeKey($data['license_key']);
        $domain = ProductLicense::normalizeDomain($data['domain']);
        $product = $data['product'] ?? 'hddland-repair';

        $proofKey = 'license_otp_proof:'.$key.':'.$domain;
        $proof = Cache::pull($proofKey);
        // Allow only with fresh proof from confirmOtp internal set — external activate without proof blocked
        if (! $proof && empty($data['otp_proof'])) {
            return response()->json([
                'ok' => false,
                'message' => 'فعال‌سازی فقط پس از تأیید پیامک مجاز است.',
                'purchase_url' => config('license.purchase_url', 'https://hdd-land.ir'),
            ], 403);
        }

        $result = $this->performActivation($key, $domain, $product, $data['version'] ?? null, $request->ip());
        if (! ($result['ok'] ?? false)) {
            return response()->json(['ok' => false, 'message' => $result['message']], $result['http'] ?? 422);
        }

        return response()->json($result);
    }

    public function verify(Request $request)
    {
        $data = $request->validate([
            'license_key' => ['required', 'string', 'max:64'],
            'domain' => ['required', 'string', 'max:190'],
            'token' => ['required', 'string', 'max:128'],
            'version' => ['nullable', 'string', 'max:30'],
        ]);

        $key = ProductLicense::normalizeKey($data['license_key']);
        $domain = ProductLicense::normalizeDomain($data['domain']);
        $license = ProductLicense::query()->where('license_key', $key)->first();

        if (! $license || $license->status !== 'active') {
            return response()->json([
                'ok' => false,
                'reason' => 'inactive',
                'message' => 'لایسنس فعال نیست.',
                'purchase_url' => config('license.purchase_url', 'https://hdd-land.ir'),
            ], 403);
        }
        if ($license->domain !== $domain) {
            // Host/domain moved without official transfer → treat as blocked on this install.
            $meta = $license->meta ?? [];
            $meta['domain_mismatch_hits'] = (int) ($meta['domain_mismatch_hits'] ?? 0) + 1;
            $meta['last_mismatch_domain'] = $domain;
            $meta['last_mismatch_at'] = now()->toDateTimeString();
            $license->forceFill([
                'meta' => $meta,
                'last_check_at' => now(),
                'last_check_ip' => $request->ip(),
            ])->save();

            return response()->json([
                'ok' => false,
                'reason' => 'domain_mismatch',
                'message' => 'این لایسنس برای دامنه دیگری ثبت شده است'
                    .($license->domain ? ' ('.$license->domain.')' : '')
                    .'. نصب روی این هاست/دامنه بلاک شد. برای انتقال یا خرید لایسنس جدید به سرزمین هارد مراجعه کنید.',
                'registered_domain' => $license->domain,
                'purchase_url' => config('license.purchase_url', 'https://hdd-land.ir'),
            ], 403);
        }
        if (! hash_equals((string) $license->token, $data['token'])) {
            return response()->json([
                'ok' => false,
                'reason' => 'inactive',
                'message' => 'توکن لایسنس نامعتبر است. نصب را از نو با تأیید پیامک انجام دهید یا لایسنس بخرید.',
                'purchase_url' => config('license.purchase_url', 'https://hdd-land.ir'),
            ], 403);
        }
        if ($license->expires_at && $license->expires_at->isPast()) {
            $license->update(['status' => 'expired']);

            return response()->json([
                'ok' => false,
                'reason' => 'expired',
                'message' => 'اعتبار لایسنس گذشته است. برای تمدید به سرزمین هارد مراجعه کنید.',
                'purchase_url' => config('license.purchase_url', 'https://hdd-land.ir'),
            ], 423);
        }

        $license->forceFill([
            'last_check_at' => now(),
            'check_count' => (int) $license->check_count + 1,
            'last_check_ip' => $request->ip(),
            'last_check_version' => (string) ($request->input('version') ?: ($license->meta['version'] ?? null)),
        ])->save();

        return response()->json([
            'ok' => true,
            'message' => 'معتبر',
            'plan' => $license->plan_label,
            'plan_code' => $license->plan_code,
            'plan_months' => $license->plan_months,
            'price_toman' => (int) $license->price_toman,
            'activated_at' => optional($license->activated_at)?->toDateString(),
            'expires_at' => optional($license->expires_at)?->toDateString(),
        ]);
    }

    /**
     * @return array{ok:bool,message?:string,http?:int,license?:ProductLicense}
     */
    private function precheckLicense(string $key, string $domain, string $product): array
    {
        $license = ProductLicense::query()->where('license_key', $key)->first();
        if (! $license) {
            return ['ok' => false, 'message' => 'سریال یافت نشد.', 'http' => 404];
        }
        if ($license->product !== $product) {
            return ['ok' => false, 'message' => 'این سریال برای این محصول نیست.', 'http' => 422];
        }
        if ($license->status === 'revoked') {
            return ['ok' => false, 'message' => 'این سریال باطل شده است.', 'http' => 423];
        }
        if ($license->expires_at && $license->expires_at->isPast()) {
            $license->update(['status' => 'expired']);

            return ['ok' => false, 'message' => 'اعتبار سریال به پایان رسیده است.', 'http' => 423];
        }
        if ($license->status === 'active' && $license->domain && $license->domain !== $domain) {
            return [
                'ok' => false,
                'message' => 'این سریال قبلاً روی دامنه دیگری فعال شده است ('.$license->domain.'). برای انتقال با پشتیبانی سرزمین هارد هماهنگ کنید یا لایسنس جدید بخرید.',
                'http' => 409,
            ];
        }

        return ['ok' => true, 'license' => $license];
    }

    /**
     * @return array<string, mixed>
     */
    private function performActivation(string $key, string $domain, string $product, ?string $version, ?string $ip): array
    {
        $check = $this->precheckLicense($key, $domain, $product);
        if (! ($check['ok'] ?? false)) {
            return ['ok' => false, 'message' => $check['message'], 'http' => $check['http'] ?? 422];
        }

        /** @var ProductLicense $license */
        $license = $check['license'];

        $token = hash_hmac(
            'sha256',
            $key.'|'.$domain.'|'.($license->id),
            (string) config('license.issuer_secret', config('app.key'))
        );

        $wasUnused = $license->status === 'unused' || ! $license->activated_at;
        $activatedAt = $license->activated_at ?: now();

        $startFrom = (string) (($license->meta['start_from'] ?? 'activate'));
        if ($wasUnused && $startFrom !== 'issue' && ! $license->expires_at && ! empty($license->plan_months)) {
            $license->applyPlanExpiry($activatedAt);
        }

        $license->fill([
            'domain' => $domain,
            'status' => 'active',
            'token' => $token,
            'activated_at' => $activatedAt,
            'last_check_at' => now(),
            'last_check_ip' => $ip,
            'last_check_version' => $version,
            'check_count' => max(1, (int) $license->check_count),
            'meta' => array_merge($license->meta ?? [], [
                'version' => $version,
                'activated_ip' => $ip,
                'activated_via' => 'otp',
            ]),
        ])->save();

        return [
            'ok' => true,
            'message' => 'لایسنس پس از تأیید پیامک فعال شد.',
            'token' => $token,
            'domain' => $domain,
            'plan' => $license->plan_label,
            'plan_code' => $license->plan_code,
            'plan_months' => $license->plan_months,
            'price_toman' => (int) $license->price_toman,
            'activated_at' => optional($license->activated_at)?->toDateString(),
            'expires_at' => optional($license->expires_at)?->toDateString(),
            'purchase_url' => config('license.purchase_url', 'https://hdd-land.ir'),
        ];
    }

    private function otpCacheKey(string $key, string $domain): string
    {
        return 'license_install_otp:'.sha1($key.'|'.$domain);
    }

    private function maskPhone(string $phone): string
    {
        $len = strlen($phone);
        if ($len < 8) {
            return '****';
        }

        return substr($phone, 0, 4).str_repeat('*', max(3, $len - 7)).substr($phone, -3);
    }
}
