<?php

namespace App\Http\Controllers;

use App\Models\ProductLicense;
use App\Services\AppUpdateService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Seller-side API: customers check / download app updates with a valid license.
 */
class AppUpdateApiController extends Controller
{
    public function __construct(private AppUpdateService $updates)
    {
    }

    public function latest(Request $request)
    {
        $data = $request->validate([
            'license_key' => ['required', 'string', 'max:64'],
            'domain' => ['required', 'string', 'max:190'],
            'token' => ['required', 'string', 'max:128'],
            'version' => ['nullable', 'string', 'max:30'],
            'channel' => ['nullable', 'string', 'max:30'],
            'product' => ['nullable', 'string', 'max:60'],
        ]);

        $auth = $this->authorizeLicense($data);
        if ($auth !== true) {
            return $auth;
        }

        $local = $this->updates->localLatest((string) ($data['channel'] ?? 'stable'));
        $licenseOk = [
            'valid' => true,
            'code' => 'valid',
            'label' => 'معتبر',
            'message' => 'لایسنس معتبر است و امکان دریافت آپدیت وجود دارد.',
            'can_update' => true,
        ];

        if (! ($local['ok'] ?? false)) {
            return response()->json([
                'ok' => true,
                'has_update' => false,
                'latest' => null,
                'message' => $local['message'] ?? 'آپدیتی منتشر نشده است.',
                'changelog' => [],
                'license' => $licenseOk,
            ]);
        }

        $current = (string) ($data['version'] ?? '0.0.0');
        $latest = (string) $local['latest'];
        $has = $this->updates->versionCompare($latest, $current) > 0;

        return response()->json([
            'ok' => true,
            'has_update' => $has,
            'latest' => $latest,
            'current' => $current,
            'released_at' => $local['released_at'] ?? null,
            'changelog' => $local['changelog'] ?? [],
            'sha256' => $local['sha256'] ?? null,
            'message' => $has ? ('نسخه '.$latest.' موجود است.') : 'به‌روز هستید.',
            'license' => $licenseOk,
        ]);
    }

    public function download(Request $request)
    {
        $data = $request->validate([
            'license_key' => ['required', 'string', 'max:64'],
            'domain' => ['required', 'string', 'max:190'],
            'token' => ['required', 'string', 'max:128'],
            'version' => ['nullable', 'string', 'max:30'],
            'channel' => ['nullable', 'string', 'max:30'],
            'product' => ['nullable', 'string', 'max:60'],
        ]);

        $auth = $this->authorizeLicense($data);
        if ($auth !== true) {
            return $auth;
        }

        $version = (string) ($data['version'] ?? '');
        $path = $this->updates->sellerZipPath($version);
        if (! $path) {
            return response()->json(['ok' => false, 'message' => 'فایل آپدیت یافت نشد.'], 404);
        }

        $sha = hash_file('sha256', $path);

        return response()->download($path, basename($path), [
            'X-Update-Sha256' => $sha,
            'Content-Type' => 'application/zip',
        ]);
    }

    /**
     * @param  array{license_key:string,domain:string,token:string,product?:string}  $data
     * @return true|\Illuminate\Http\JsonResponse
     */
    private function authorizeLicense(array $data)
    {
        $key = ProductLicense::normalizeKey($data['license_key']);
        $domain = ProductLicense::normalizeDomain($data['domain']);
        $product = $data['product'] ?? 'hddland-repair';

        $license = ProductLicense::query()->where('license_key', $key)->first();
        if (! $license || $license->status !== 'active') {
            $reason = ($license && $license->status === 'revoked') ? 'revoked' : 'invalid';
            $message = $reason === 'revoked' ? 'لایسنس باطل شده است.' : 'لایسنس فعال نیست.';

            return response()->json([
                'ok' => false,
                'reason' => $reason,
                'message' => $message,
                'license' => [
                    'valid' => false,
                    'code' => $reason,
                    'label' => $reason === 'revoked' ? 'باطل شده' : 'نامعتبر',
                    'message' => $message,
                    'can_update' => false,
                ],
            ], 403);
        }
        if ($license->product && $license->product !== $product) {
            return response()->json([
                'ok' => false,
                'reason' => 'product_mismatch',
                'message' => 'محصول لایسنس مطابقت ندارد.',
                'license' => [
                    'valid' => false,
                    'code' => 'invalid',
                    'label' => 'نامعتبر',
                    'message' => 'محصول لایسنس مطابقت ندارد.',
                    'can_update' => false,
                ],
            ], 422);
        }
        if ($license->domain !== $domain || ! hash_equals((string) $license->token, $data['token'])) {
            return response()->json([
                'ok' => false,
                'reason' => 'domain_mismatch',
                'message' => 'توکن یا دامنه نامعتبر است.',
                'license' => [
                    'valid' => false,
                    'code' => 'domain_mismatch',
                    'label' => 'دامنه نامعتبر',
                    'message' => 'توکن یا دامنه نامعتبر است.',
                    'can_update' => false,
                    'domain' => $license->domain,
                ],
            ], 403);
        }
        if ($license->expires_at && $license->expires_at->isPast()) {
            $license->update(['status' => 'expired']);

            return response()->json([
                'ok' => false,
                'reason' => 'expired',
                'message' => 'اعتبار لایسنس گذشته است.',
                'expires_at' => optional($license->expires_at)?->toDateString(),
                'license' => [
                    'valid' => false,
                    'code' => 'expired',
                    'label' => 'منقضی شده',
                    'message' => 'اعتبار لایسنس گذشته است.',
                    'can_update' => false,
                    'expires_at' => optional($license->expires_at)?->toDateString(),
                ],
            ], 423);
        }

        $license->forceFill([
            'last_check_at' => now(),
            'check_count' => (int) $license->check_count + 1,
            'last_check_ip' => request()->ip(),
            'last_check_version' => (string) (request()->input('version') ?: $license->last_check_version),
        ])->save();

        return true;
    }
}
