<?php

namespace App\Http\Controllers;

use App\Models\ProductLicense;
use Illuminate\Http\Request;

/**
 * Seller-side license activation API used by the web installer on customer hosts.
 */
class LicenseApiController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $licenses = ProductLicense::query()->latest('id')->paginate(30);

        return view('licenses.index', compact('licenses'));
    }

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

        $license->fill([
            'domain' => $domain,
            'status' => 'active',
            'token' => $token,
            'activated_at' => $license->activated_at ?: now(),
            'last_check_at' => now(),
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
            'expires_at' => optional($license->expires_at)?->toDateString(),
        ]);
    }

    public function verify(Request $request)
    {
        $data = $request->validate([
            'license_key' => ['required', 'string', 'max:64'],
            'domain' => ['required', 'string', 'max:190'],
            'token' => ['required', 'string', 'max:128'],
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

        $license->forceFill(['last_check_at' => now()])->save();

        return response()->json(['ok' => true, 'message' => 'معتبر']);
    }

    /** Admin helper: issue a new unused key. */
    public function issue(Request $request)
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $data = $request->validate([
            'customer_name' => ['nullable', 'string', 'max:120'],
            'customer_phone' => ['nullable', 'string', 'max:30'],
            'expires_at' => ['nullable', 'date'],
        ]);

        $key = strtoupper(
            substr(bin2hex(random_bytes(2)), 0, 4).'-'.
            substr(bin2hex(random_bytes(2)), 0, 4).'-'.
            substr(bin2hex(random_bytes(2)), 0, 4).'-'.
            substr(bin2hex(random_bytes(2)), 0, 4)
        );

        $row = ProductLicense::query()->create([
            'license_key' => $key,
            'customer_name' => $data['customer_name'] ?? null,
            'customer_phone' => $data['customer_phone'] ?? null,
            'product' => 'hddland-repair',
            'status' => 'unused',
            'expires_at' => $data['expires_at'] ?? null,
        ]);

        return back()->with('success', 'سریال صادر شد: '.$row->license_key);
    }
}
