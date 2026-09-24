<?php

namespace App\Http\Controllers;

use App\Models\ProductLicense;
use Illuminate\Http\Request;

/**
 * Seller-side license activation API used by the web installer on customer hosts.
 */
class LicenseApiController extends Controller
{
    public function activate(Request $request)
    {
        $data = $request->validate([
            'license_key' => ['required', 'string', 'max:64'],
            'domain' => ['required', 'string', 'max:190'],
            'product' => ['nullable', 'string', 'max:60'],
            'version' => ['nullable', 'string', 'max:30'],
        ]);

        $key = ProductLicense::normalizeKey($data['license_key']);
        $domain = ProductLicense::normalizeDomain($data['domain']);
        $product = $data['product'] ?? 'hddland-repair';

        $license = ProductLicense::query()->where('license_key', $key)->first();
        if (! $license) {
            return response()->json(['ok' => false, 'message' => 'سریال یافت نشد.'], 404);
        }
        if ($license->product !== $product) {
            return response()->json(['ok' => false, 'message' => 'این سریال برای این محصول نیست.'], 422);
        }
        if ($license->status === 'revoked') {
            return response()->json(['ok' => false, 'message' => 'این سریال باطل شده است.'], 423);
        }
        if ($license->expires_at && $license->expires_at->isPast()) {
            $license->update(['status' => 'expired']);

            return response()->json(['ok' => false, 'message' => 'اعتبار سریال به پایان رسیده است.'], 423);
        }

        if ($license->status === 'active' && $license->domain && $license->domain !== $domain) {
            return response()->json([
                'ok' => false,
                'message' => 'این سریال قبلاً روی دامنه دیگری فعال شده است ('.$license->domain.').',
            ], 409);
        }

        $token = hash_hmac(
            'sha256',
            $key.'|'.$domain.'|'.($license->id),
            (string) config('license.issuer_secret', config('app.key'))
        );

        $wasUnused = $license->status === 'unused' || ! $license->activated_at;
        $activatedAt = $license->activated_at ?: now();

        // Timed plans: start countdown at first activation (unless expires_at already set at issue).
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
            'last_check_ip' => $request->ip(),
            'last_check_version' => $data['version'] ?? null,
            'check_count' => max(1, (int) $license->check_count),
            'meta' => array_merge($license->meta ?? [], [
                'version' => $data['version'] ?? null,
                'activated_ip' => $request->ip(),
            ]),
        ])->save();

        return response()->json([
            'ok' => true,
            'message' => 'لایسنس فعال شد.',
            'token' => $token,
            'domain' => $domain,
            'plan' => $license->plan_label,
            'plan_code' => $license->plan_code,
            'plan_months' => $license->plan_months,
            'price_toman' => (int) $license->price_toman,
            'activated_at' => optional($license->activated_at)?->toDateString(),
            'expires_at' => optional($license->expires_at)?->toDateString(),
        ]);
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
            return response()->json(['ok' => false, 'message' => 'لایسنس فعال نیست.'], 403);
        }
        if ($license->domain !== $domain || ! hash_equals((string) $license->token, $data['token'])) {
            return response()->json(['ok' => false, 'message' => 'توکن یا دامنه لایسنس نامعتبر است.'], 403);
        }
        if ($license->expires_at && $license->expires_at->isPast()) {
            $license->update(['status' => 'expired']);

            return response()->json(['ok' => false, 'message' => 'اعتبار لایسنس گذشته است.'], 423);
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
}
