<?php

namespace App\Http\Controllers;

use App\Models\ProductLicense;
use App\Services\NiazpardazSmsService;
use Illuminate\Http\Request;

/**
 * Seller admin panel: issue / report / monitor customer install licenses.
 */
class LicenseAdminController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $q = ProductLicense::query()->latest('id');

        $status = trim((string) $request->query('status', ''));
        if ($status !== '' && in_array($status, ['unused', 'active', 'revoked', 'expired'], true)) {
            $q->where('status', $status);
        }

        $online = $request->query('online');
        if ($online === '1') {
            $q->where('status', 'active')
                ->where('last_check_at', '>=', now()->subDays(7));
        } elseif ($online === '0') {
            $q->where('status', 'active')
                ->where(function ($w) {
                    $w->whereNull('last_check_at')
                        ->orWhere('last_check_at', '<', now()->subDays(7));
                });
        }

        $search = trim((string) $request->query('q', ''));
        if ($search !== '') {
            $q->where(function ($w) use ($search) {
                $w->where('license_key', 'like', '%'.$search.'%')
                    ->orWhere('customer_name', 'like', '%'.$search.'%')
                    ->orWhere('customer_phone', 'like', '%'.$search.'%')
                    ->orWhere('customer_email', 'like', '%'.$search.'%')
                    ->orWhere('domain', 'like', '%'.$search.'%');
            });
        }

        $licenses = $q->paginate(40)->withQueryString();
        $stats = $this->stats();

        return view('licenses.index', compact('licenses', 'stats', 'status', 'online', 'search'));
    }

    public function online(Request $request)
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $onlineRows = ProductLicense::query()
            ->where('status', 'active')
            ->where('last_check_at', '>=', now()->subDays(7))
            ->orderByDesc('last_check_at')
            ->get();

        $offlineRows = ProductLicense::query()
            ->where('status', 'active')
            ->where(function ($w) {
                $w->whereNull('last_check_at')
                    ->orWhere('last_check_at', '<', now()->subDays(7));
            })
            ->orderByDesc('activated_at')
            ->get();

        $stats = $this->stats();

        return view('licenses.online', compact('onlineRows', 'offlineRows', 'stats'));
    }

    public function issue(Request $request)
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $data = $request->validate([
            'customer_name' => ['nullable', 'string', 'max:120'],
            'customer_phone' => ['nullable', 'string', 'max:30'],
            'customer_email' => ['nullable', 'email', 'max:160'],
            'domain_hint' => ['nullable', 'string', 'max:190'],
            'expires_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'send_sms' => ['nullable', 'boolean'],
        ]);

        $key = ProductLicense::generateKey();
        $domainHint = ! empty($data['domain_hint'])
            ? ProductLicense::normalizeDomain($data['domain_hint'])
            : null;

        $row = ProductLicense::query()->create([
            'license_key' => $key,
            'customer_name' => $data['customer_name'] ?? null,
            'customer_phone' => $data['customer_phone'] ?? null,
            'customer_email' => $data['customer_email'] ?? null,
            'product' => 'hddland-repair',
            'status' => 'unused',
            'expires_at' => $data['expires_at'] ?? null,
            'notes' => $data['notes'] ?? null,
            'meta' => array_filter([
                'domain_hint' => $domainHint,
                'issued_by' => $request->user()->id,
                'issued_by_name' => $request->user()->name,
            ]),
        ]);

        $msg = 'سریال صادر شد: '.$row->license_key;

        if ($request->boolean('send_sms') && ! empty($row->customer_phone)) {
            $sms = $this->sendLicenseSms($row);
            $msg .= $sms['ok']
                ? ' — پیامک ارسال شد.'
                : ' — پیامک ارسال نشد: '.($sms['message'] ?? '');
        }

        return redirect()->route('licenses.index')->with('success', $msg);
    }

    public function sendSms(Request $request, ProductLicense $license, NiazpardazSmsService $sms)
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $result = $this->sendLicenseSms($license, $sms);
        if (! ($result['ok'] ?? false)) {
            return back()->with('error', $result['message'] ?? 'ارسال پیامک ناموفق بود.');
        }

        return back()->with('success', 'سریال برای '.$license->customer_phone.' پیامک شد.');
    }

    public function revoke(Request $request, ProductLicense $license)
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $license->update([
            'status' => 'revoked',
            'token' => null,
        ]);

        return back()->with('success', 'لایسنس باطل شد: '.$license->license_key);
    }

    public function unbind(Request $request, ProductLicense $license)
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $license->update([
            'status' => 'unused',
            'domain' => null,
            'token' => null,
            'activated_at' => null,
            'last_check_at' => null,
            'last_check_ip' => null,
            'last_check_version' => null,
            'check_count' => 0,
        ]);

        return back()->with('success', 'قفل دامنه برداشته شد. سریال دوباره قابل نصب است: '.$license->license_key);
    }

    public function extend(Request $request, ProductLicense $license)
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $data = $request->validate([
            'expires_at' => ['nullable', 'date'],
        ]);

        $license->update([
            'expires_at' => $data['expires_at'] ?? null,
            'status' => $license->status === 'expired' ? 'active' : $license->status,
        ]);

        return back()->with('success', 'تاریخ انقضا به‌روز شد.');
    }

    /** @return array<string, int> */
    private function stats(): array
    {
        $onlineSince = now()->subDays(7);

        return [
            'total' => ProductLicense::query()->count(),
            'unused' => ProductLicense::query()->where('status', 'unused')->count(),
            'active' => ProductLicense::query()->where('status', 'active')->count(),
            'online' => ProductLicense::query()
                ->where('status', 'active')
                ->where('last_check_at', '>=', $onlineSince)
                ->count(),
            'offline' => ProductLicense::query()
                ->where('status', 'active')
                ->where(function ($w) use ($onlineSince) {
                    $w->whereNull('last_check_at')->orWhere('last_check_at', '<', $onlineSince);
                })
                ->count(),
            'revoked' => ProductLicense::query()->where('status', 'revoked')->count(),
            'expired' => ProductLicense::query()->where('status', 'expired')->count(),
            'issued_30d' => ProductLicense::query()->where('created_at', '>=', now()->subDays(30))->count(),
            'activated_30d' => ProductLicense::query()->where('activated_at', '>=', now()->subDays(30))->count(),
        ];
    }

    /** @return array{ok:bool,message?:string} */
    private function sendLicenseSms(ProductLicense $license, ?NiazpardazSmsService $sms = null): array
    {
        $phone = preg_replace('/\D+/', '', (string) $license->customer_phone) ?: '';
        if ($phone === '') {
            return ['ok' => false, 'message' => 'موبایل مشتری ثبت نشده.'];
        }

        $shop = shop_name();
        $lines = [
            $shop,
            'سریال نصب نرم‌افزار تعمیرگاه:',
            $license->license_key,
            'در ویزارد نصب وارد کنید.',
        ];
        if ($license->expires_at) {
            $lines[] = 'انقضا: '.jalali_date($license->expires_at);
        }

        $sms = $sms ?: app(NiazpardazSmsService::class);

        return $sms->send($phone, implode("\n", $lines));
    }
}
