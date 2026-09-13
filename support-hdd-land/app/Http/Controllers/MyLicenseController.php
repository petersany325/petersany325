<?php

namespace App\Http\Controllers;

use App\Support\LicenseStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

/**
 * Customer-only license status / renew guidance (not seller license factory).
 */
class MyLicenseController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()?->isAdmin(), 403);
        abort_unless(LicenseStatus::isCustomerSite(), 404);

        $status = LicenseStatus::current();
        $focus = (string) $request->query('focus', '');

        return view('my-license.index', [
            'license' => $status,
            'focus' => $focus,
            'officePhone' => shop_office_phone(),
        ]);
    }

    public function refresh(Request $request)
    {
        abort_unless($request->user()?->isAdmin(), 403);
        abort_unless(LicenseStatus::isCustomerSite(), 404);

        $server = rtrim((string) config('license.server', 'https://support.hdd-land.ir'), '/');
        $key = trim((string) config('license.key'));
        $token = (string) config('license.token');
        $domain = (string) config('license.domain');

        if ($server === '' || $key === '' || $token === '') {
            return back()->with('error', 'تنظیمات لایسنس ناقص است.');
        }

        try {
            $response = Http::timeout(12)
                ->asForm()
                ->acceptJson()
                ->post($server.'/license/verify', [
                    'license_key' => $key,
                    'domain' => $domain !== '' ? $domain : $request->getHost(),
                    'token' => $token,
                    'version' => app(\App\Services\AppUpdateService::class)->installedVersion(),
                ]);
            $json = $response->json();
            if (! is_array($json) || ($json['ok'] ?? false) !== true) {
                return back()->with('error', is_array($json) ? (string) ($json['message'] ?? 'لایسنس نامعتبر است.') : 'ارتباط با سرور لایسنس ناموفق بود.');
            }
            LicenseStatus::store([
                'ok' => true,
                'message' => $json['message'] ?? 'معتبر',
                'plan' => $json['plan'] ?? null,
                'plan_code' => $json['plan_code'] ?? null,
                'plan_months' => $json['plan_months'] ?? null,
                'price_toman' => $json['price_toman'] ?? null,
                'activated_at' => $json['activated_at'] ?? null,
                'expires_at' => $json['expires_at'] ?? null,
            ]);

            return back()->with('success', 'وضعیت لایسنس به‌روز شد.');
        } catch (\Throwable $e) {
            return back()->with('error', 'خطا در ارتباط: '.$e->getMessage());
        }
    }
}
