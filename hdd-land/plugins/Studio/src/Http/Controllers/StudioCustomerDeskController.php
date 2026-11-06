<?php

namespace Plugins\Studio\src\Http\Controllers;

use App\Support\StudioCustomerDesk;
use App\Support\StudioOffers;
use App\Support\StudioPay;
use App\Support\StudioPlugins;
use App\Support\StudioStaffDesk;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Plugins\Studio\Plugin as StudioPlugin;
use Symfony\Component\HttpFoundation\Response;

class StudioCustomerDeskController
{
    public function dispatchPath(Request $request, string $path): Response
    {
        $tab = trim(substr($path, strlen('/account')), '/');
        if ($tab === '') {
            $tab = 'home';
        }
        if ($request->isMethod('post')) {
            return match ($tab) {
                'tickets', 'support' => $this->storeTicket($request),
                'sell', 'buy' => $this->storeSale($request),
                default => redirect()->to(url('/account')),
            };
        }

        return match ($tab) {
            'buy', 'shop' => $this->screen('buy', 'خرید'),
            'sell' => $this->screen('sell', 'فروش و ثبت سفارش'),
            'orders' => $this->screen('orders', 'سفارش‌ها'),
            'invoices' => $this->screen('invoices', 'فاکتورها'),
            'tickets' => $this->screen('tickets', 'تیکت‌ها'),
            'support' => $this->screen('support', 'پشتیبانی'),
            'wallet' => $this->screen('wallet', 'کیف پول'),
            'serials' => $this->screen('serials', 'سریال‌ها و گارانتی'),
            'prices' => $this->screen('prices', 'قیمت‌ها'),
            default => $this->screen('home', 'کارتابل مشتری'),
        };
    }

    public function home(): View|RedirectResponse
    {
        $path = '/'.ltrim(request()->path(), '/');

        return $this->dispatchPath(request(), $path);
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
            return back()->with('error', 'موضوع و متن را بنویسید.');
        }
        StudioPlugins::openTicket(
            (int) ($user->id ?? 0),
            (string) ($user->name ?? 'مشتری'),
            (string) ($user->phone ?? $request->input('phone', '')),
            $subject,
            $body
        );

        return redirect()->to(url('/account/tickets'))->with('success', 'تیکت ثبت شد و پیامک برای صاحب تیکت ارسال می‌شود.');
    }

    public function storeSale(Request $request): RedirectResponse
    {
        $gate = $this->guard();
        if ($gate instanceof RedirectResponse) {
            return $gate;
        }
        $sku = trim((string) $request->input('sku', ''));
        $offer = StudioOffers::find($sku) ?? StudioPlugins::shopOffer($sku);
        if ($offer === null) {
            return back()->with('error', 'این مورد در فهرست خرید نیست.');
        }
        $gateway = $request->input('gateway') === 'melli' ? 'melli' : 'zarinpal';
        $buyer = trim((string) $request->input('buyer', (string) (Auth::user()->name ?? 'مشتری')));

        return redirect()->to(url('/pay/start?sku='.urlencode($sku).'&gateway='.$gateway.'&buyer='.urlencode($buyer).'&for=customer'));
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

        return view('studio::customer-desk', [
            'tab' => $tab,
            'title' => $title,
            'menu' => StudioCustomerDesk::menu(),
            'offers' => array_merge(StudioOffers::software(), StudioOffers::design()),
            'shopOffers' => StudioPlugins::shopProducts(),
            'orders' => StudioPlugins::orders($uid),
            'invoices' => StudioPlugins::invoices($uid),
            'tickets' => StudioPlugins::tickets($uid),
            'serials' => StudioPlugins::serials($uid),
            'wallet' => StudioPlugins::wallet($uid),
            'gateways' => StudioPay::gateways(),
            'cartCount' => StudioPlugins::cartCount(),
            'cartUrl' => StudioPlugins::cartUrl(),
            'checkoutUrl' => StudioPlugins::checkoutUrl(),
            'ticketsReady' => StudioPlugins::ticketsReady(),
            'user' => $user,
        ]);
    }

    protected function guard(): ?RedirectResponse
    {
        if (! Auth::check()) {
            return redirect()->to(url('/auth/customer'))->with('error', 'ورود مشتری با پیامک لازم است.');
        }

        return null;
    }
}
