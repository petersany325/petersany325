<?php

namespace Plugins\Studio\src\Http\Controllers;

use App\Support\StudioCart;
use App\Support\StudioPay;
use App\Support\StudioPriceList;
use App\Support\StudioStaffDesk;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;
use Plugins\Studio\Plugin as StudioPlugin;
use Plugins\Studio\src\Support\StudioSms;

class StudioCartController
{
    public function add(Request $request): RedirectResponse
    {
        StudioPlugin::ensureBooted();
        $sku = trim((string) $request->input('sku', ''));
        if (StudioPriceList::find($sku) === null) {
            return redirect()->to(url('/computer-accounting#prices'))->with('error', 'این مورد در فهرست قیمت نیست.');
        }
        StudioCart::add($sku);

        return redirect()->to(url('/computer-accounting#prices'))->with('success', 'به سبد اضافه شد.');
    }

    public function remove(Request $request): RedirectResponse
    {
        StudioPlugin::ensureBooted();
        StudioCart::remove(trim((string) $request->input('sku', '')));

        return back()->with('success', 'از سبد برداشته شد.');
    }

    public function show(): View
    {
        StudioPlugin::ensureBooted();

        return view('studio::cart', [
            'items' => StudioCart::items(),
            'totalLabel' => StudioCart::totalLabel(),
        ]);
    }

    public function pay(): View|RedirectResponse
    {
        StudioPlugin::ensureBooted();
        $items = StudioCart::items();
        if ($items === []) {
            return redirect()->to(url('/computer-accounting#prices'))->with('error', 'سبد خالی است.');
        }

        return view('studio::pay-cart', [
            'items' => $items,
            'totalLabel' => StudioCart::totalLabel(),
            'gateways' => StudioPay::gateways(),
        ]);
    }

    public function record(Request $request): RedirectResponse
    {
        StudioPlugin::ensureBooted();
        $items = StudioCart::items();
        if ($items === []) {
            return redirect()->to(url('/computer-accounting#prices'))->with('error', 'سبد خالی است.');
        }
        $buyer = mb_substr(trim((string) $request->input('buyer', '')), 0, 80);
        if ($buyer === '') {
            return back()->with('error', 'نام خریدار را بنویسید.');
        }
        $gateway = $request->input('gateway') === 'melli' ? 'melli' : 'zarinpal';
        $user = Auth::user();
        $buyerPhone = '';
        try {
            $buyerPhone = StudioSms::normalizePhone((string) $request->input('phone', $user->phone ?? ''));
        } catch (\Throwable) {
        }
        $ipKey = 'pay-cart-ip:'.$request->ip();
        $phoneKey = $buyerPhone !== '' ? 'pay-cart-phone:'.hash('sha256', $buyerPhone) : '';
        if (RateLimiter::tooManyAttempts($ipKey, 4) || ($phoneKey !== '' && RateLimiter::tooManyAttempts($phoneKey, 3))) {
            return back()->withInput()->with('error', 'تعداد ثبت سبد در این ساعت زیاد شد. کمی بعد دوباره تلاش کنید.');
        }
        RateLimiter::hit($ipKey, 3600);
        if ($phoneKey !== '') {
            RateLimiter::hit($phoneKey, 3600);
        }
        $isStaff = false;
        try {
            $isStaff = $user && ((method_exists($user, 'isStaff') && $user->isStaff()) || (method_exists($user, 'isAdmin') && $user->isAdmin()));
        } catch (\Throwable) {
        }
        $lines = [];
        foreach ($items as $row) {
            $lines[] = [
                'sku' => $row['sku'],
                'title' => $row['title'],
                'price' => $row['price'],
                'price_label' => $row['price_label'],
            ];
        }
        $offer = [
            'sku' => 'acc-cart',
            'title' => 'سبد: '.implode('، ', array_column($lines, 'title')),
            'url' => '/computer-accounting/cart',
            'price' => (string) StudioCart::total(),
            'commission' => 0,
            'buyer_id' => $isStaff ? 0 : (int) ($user->id ?? 0),
            'buyer_phone' => $buyerPhone,
            'lines' => $lines,
        ];
        $sellerId = $isStaff ? (int) ($user->id ?? 0) : 0;
        $invoice = StudioStaffDesk::recordSale($offer, $sellerId, $buyer, $gateway);
        StudioCart::clear();
        if ($isStaff) {
            $to = '/staff/desk/invoices';
        } elseif ($user) {
            $to = '/account/invoices';
        } else {
            $to = '/computer-accounting/cart';
        }

        return redirect()->to(url($to))->with('success', 'سبد در کارتابل ثبت شد. شماره '.$invoice['id']);
    }
}
