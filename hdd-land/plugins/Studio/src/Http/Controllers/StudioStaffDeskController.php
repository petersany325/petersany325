<?php

namespace Plugins\Studio\src\Http\Controllers;

use App\Support\StudioNotify;
use App\Support\StudioOffers;
use App\Support\StudioPay;
use App\Support\StudioStaffDesk;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Plugins\Studio\Plugin as StudioPlugin;
use Plugins\Studio\src\Support\StudioSms;

class StudioStaffDeskController
{
    public function home(): View|RedirectResponse
    {
        return $this->screen('home', 'میز کار نماینده');
    }

    public function buy(): View|RedirectResponse
    {
        return $this->screen('buy', 'خرید نرم‌افزار');
    }

    public function sell(): View|RedirectResponse
    {
        return $this->screen('sell', 'فروش نرم‌افزار');
    }

    public function prices(): View|RedirectResponse
    {
        return $this->screen('prices', 'قیمت‌های فروش');
    }

    public function sales(): View|RedirectResponse
    {
        return $this->screen('sales', 'فروش‌های من');
    }

    public function invoices(): View|RedirectResponse
    {
        return $this->screen('invoices', 'فاکتورها');
    }

    public function reps(): View|RedirectResponse
    {
        return $this->screen('reps', 'لیست نمایندگان');
    }

    public function tickets(): View|RedirectResponse
    {
        return $this->screen('tickets', 'تیکت‌ها');
    }

    public function sms(): View|RedirectResponse
    {
        return $this->screen('sms', 'پیامک کارتابل');
    }

    public function storeTicket(Request $request): RedirectResponse
    {
        $gate = $this->guard();
        if ($gate instanceof RedirectResponse) {
            return $gate;
        }
        $user = Auth::user();
        $subject = trim((string) $request->input('subject', ''));
        $body = trim((string) $request->input('body', ''));
        if ($subject === '' || $body === '') {
            return back()->with('error', 'موضوع و متن تیکت را بنویسید.');
        }
        $phone = '';
        try {
            $phone = StudioSms::normalizePhone((string) ($user->phone ?? $request->input('phone', '')));
        } catch (\Throwable) {
        }
        \App\Support\StudioPlugins::openTicket(
            (int) ($user->id ?? 0),
            (string) ($user->name ?? 'نماینده'),
            $phone,
            $subject,
            $body
        );

        return back()->with('success', 'تیکت ثبت شد. پیامک برای صاحب تیکت ارسال می‌شود.');
    }

    public function storePrice(Request $request): RedirectResponse
    {
        $gate = $this->guard();
        if ($gate instanceof RedirectResponse) {
            return $gate;
        }
        $user = Auth::user();
        $isAdmin = $user && method_exists($user, 'isAdmin') && $user->isAdmin();
        if (! $isAdmin) {
            return back()->with('error', 'تعریف قیمت و درصد فقط برای مدیر است.');
        }
        $sku = trim((string) $request->input('sku', ''));
        $offer = StudioOffers::find($sku);
        if ($offer === null) {
            return back()->with('error', 'این نرم‌افزار در فهرست نیست.');
        }
        $rawPrice = trim((string) $request->input('price', ''));
        $rawRate = trim((string) $request->input('commission', ''));
        $price = $rawPrice === '' ? null : (int) preg_replace('/\D+/', '', $rawPrice);
        if ($price !== null && $price < 0) {
            $price = null;
        }
        $rate = $rawRate === '' ? null : (float) str_replace(',', '.', $rawRate);
        if ($rate !== null && ($rate < 0 || $rate > 80)) {
            return back()->with('error', 'درصد سهم بین ۰ و ۸۰ است.');
        }
        StudioStaffDesk::savePrice($sku, $price, $rate);

        return back()->with('success', 'قیمت و درصد این نرم‌افزار ذخیره شد.');
    }

    public function storeSale(Request $request): RedirectResponse
    {
        $gate = $this->guard();
        if ($gate instanceof RedirectResponse) {
            return $gate;
        }
        $sku = trim((string) $request->input('sku', ''));
        $offer = StudioOffers::find($sku);
        if ($offer === null) {
            return back()->with('error', 'این نرم‌افزار در فهرست فروش نیست.');
        }
        $buyer = trim((string) $request->input('buyer', 'مشتری'));
        $gateway = $request->input('gateway') === 'melli' ? 'melli' : 'zarinpal';

        return redirect()->to(url('/pay/start?sku='.urlencode($sku).'&gateway='.$gateway.'&buyer='.urlencode($buyer)));
    }

    /** @return View|RedirectResponse */
    protected function screen(string $tab, string $title): View|RedirectResponse
    {
        $gate = $this->guard();
        if ($gate instanceof RedirectResponse) {
            return $gate;
        }
        StudioPlugin::ensureBooted();
        $user = Auth::user();
        $uid = (int) ($user->id ?? 0);
        $sms = [];
        try {
            $sms = StudioSms::adminStatus();
        } catch (\Throwable) {
            $sms = ['ready' => false];
        }

        $offers = [];
        foreach (StudioOffers::catalog() as $row) {
            if (($row['kind'] ?? '') === 'design') {
                continue;
            }
            $row['quote'] = StudioOffers::quote($row, $uid);
            $offers[] = $row;
        }

        return view('studio::staff-desk', [
            'tab' => $tab,
            'title' => $title,
            'menu' => StudioStaffDesk::menu(),
            'mobileMenu' => StudioStaffDesk::mobileMenu(),
            'offers' => $offers,
            'selected' => trim((string) request('sku', '')),
            'isAdmin' => $user && method_exists($user, 'isAdmin') && $user->isAdmin(),
            'designOffers' => StudioOffers::design(),
            'sales' => StudioStaffDesk::salesFor($uid),
            'tickets' => \App\Support\StudioPlugins::tickets($uid, true),
            'reps' => StudioStaffDesk::reps(),
            'balance' => StudioStaffDesk::balance($uid),
            'gateways' => StudioPay::gateways(),
            'sms' => $sms,
            'user' => $user,
        ]);
    }

    protected function guard(): ?RedirectResponse
    {
        if (! Auth::check()) {
            return redirect()->to(url('/auth/staff'))->with('error', 'ورود کارمند با نام کاربری و کد پیامک لازم است.');
        }
        $user = Auth::user();
        try {
            if ($user && (method_exists($user, 'isStaff') && $user->isStaff() || method_exists($user, 'isAdmin') && $user->isAdmin())) {
                return null;
            }
        } catch (\Throwable) {
        }
        $role = is_object($user) ? (string) ($user->role ?? '') : '';
        if (in_array($role, ['staff', 'admin', 'seller'], true) || ! empty($user->is_admin)) {
            return null;
        }

        return redirect()->to(url('/auth/staff'))->with('error', 'این حساب کارمند فعال نیست.');
    }
}
