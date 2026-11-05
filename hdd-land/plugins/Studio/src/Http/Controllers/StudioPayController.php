<?php

namespace Plugins\Studio\src\Http\Controllers;

use App\Support\StudioOffers;
use App\Support\StudioPay;
use App\Support\StudioStaffDesk;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Plugins\Studio\Plugin as StudioPlugin;
use Plugins\Studio\src\Support\StudioSms;

class StudioPayController
{
    public function start(Request $request): View|RedirectResponse
    {
        StudioPlugin::ensureBooted();
        $sku = trim((string) $request->query('sku', $request->input('sku', '')));
        $offer = StudioOffers::find($sku);
        if ($offer === null) {
            return redirect()->to(url('/contact'))->with('error', 'این محصول برای فروش پیدا نشد.');
        }
        $gateway = $request->query('gateway', $request->input('gateway', 'zarinpal'));
        if ($gateway !== 'melli') {
            $gateway = 'zarinpal';
        }

        return view('studio::pay', [
            'offer' => $offer,
            'gateway' => $gateway,
            'gateways' => StudioPay::gateways(),
            'buyer' => trim((string) $request->query('buyer', '')),
        ]);
    }

    public function record(Request $request): RedirectResponse
    {
        StudioPlugin::ensureBooted();
        $sku = trim((string) $request->input('sku', ''));
        $offer = StudioOffers::find($sku);
        if ($offer === null) {
            return redirect()->to(url('/contact'))->with('error', 'محصول نامعتبر است.');
        }
        $gateway = $request->input('gateway') === 'melli' ? 'melli' : 'zarinpal';
        $user = Auth::user();
        $buyer = trim((string) $request->input('buyer', (string) ($user->name ?? 'مشتری')));
        $buyerPhone = '';
        try {
            $buyerPhone = StudioSms::normalizePhone((string) $request->input('phone', $user->phone ?? ''));
        } catch (\Throwable) {
        }
        $invoice = StudioStaffDesk::recordSale($offer, (int) ($user->id ?? 0), $buyer, $gateway);
        $invoice['buyer_phone'] = $buyerPhone;
        if (class_exists(\App\Support\StudioNotify::class) && $buyerPhone !== '') {
            \App\Support\StudioNotify::invoice($invoice);
        }

        return redirect()->to(url('/staff/desk/invoices'))->with('success', 'فاکتور '.$invoice['id'].' ثبت شد. سهم نماینده در کارتابل آمده است.');
    }
}
