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
        if (! ($local['ok'] ?? false)) {
            return response()->json([
                'ok' => true,
                'has_update' => false,
                'latest' => null,
                'message' => $local['message'] ?? 'آپدیتی منتشر نشده است.',
                'changelog' => [],
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
            return response()->json(['ok' => false, 'message' => 'لایسنس فعال نیست.'], 403);
        }
        if ($license->product && $license->product !== $product) {
            return response()->json(['ok' => false, 'message' => 'محصول لایسنس مطابقت ندارد.'], 422);
        }
        if ($license->domain !== $domain || ! hash_equals((string) $license->token, $data['token'])) {
            return response()->json(['ok' => false, 'message' => 'توکن یا دامنه نامعتبر است.'], 403);
        }
        if ($license->expires_at && $license->expires_at->isPast()) {
            $license->update(['status' => 'expired']);

            return response()->json(['ok' => false, 'message' => 'اعتبار لایسنس گذشته است.'], 423);
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
