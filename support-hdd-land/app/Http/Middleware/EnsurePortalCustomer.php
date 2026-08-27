<?php

namespace App\Http\Middleware;

use App\Models\Customer;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpFoundation\Response;

class EnsurePortalCustomer
{
    public const REMEMBER_COOKIE = 'portal_remember';

    public function handle(Request $request, Closure $next): Response
    {
        $id = $request->session()->get('portal_customer_id');

        if (! $id) {
            $id = $this->restoreFromRememberCookie($request);
        }

        if (! $id) {
            return redirect()->route('portal.login');
        }

        $customer = Customer::query()->find($id);
        if (! $customer) {
            $request->session()->forget('portal_customer_id');
            Cookie::queue(Cookie::forget(self::REMEMBER_COOKIE));

            return redirect()->route('portal.login');
        }

        // keep session alive on each authenticated hit
        $request->session()->put('portal_customer_id', $customer->id);
        $request->session()->put('portal_last_seen', now()->timestamp);

        $request->attributes->set('portalCustomer', $customer);
        view()->share('portalCustomer', $customer);

        return $next($request);
    }

    public function restoreCustomerId(Request $request): ?int
    {
        return $this->restoreFromRememberCookie($request);
    }

    private function restoreFromRememberCookie(Request $request): ?int
    {
        $raw = $request->cookie(self::REMEMBER_COOKIE);
        if (! $raw) {
            return null;
        }

        try {
            $payload = decrypt($raw);
        } catch (\Throwable) {
            Cookie::queue(Cookie::forget(self::REMEMBER_COOKIE));

            return null;
        }

        if (! is_array($payload)) {
            return null;
        }

        $exp = (int) ($payload['exp'] ?? 0);
        $cid = (int) ($payload['cid'] ?? 0);
        if ($cid < 1 || $exp < now()->timestamp) {
            Cookie::queue(Cookie::forget(self::REMEMBER_COOKIE));

            return null;
        }

        $request->session()->put('portal_customer_id', $cid);

        return $cid;
    }
}
