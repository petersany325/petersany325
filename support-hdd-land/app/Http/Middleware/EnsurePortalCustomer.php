<?php

namespace App\Http\Middleware;

use App\Models\Customer;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePortalCustomer
{
    public function handle(Request $request, Closure $next): Response
    {
        $id = $request->session()->get('portal_customer_id');
        if (! $id) {
            return redirect()->route('portal.login');
        }

        $customer = Customer::query()->find($id);
        if (! $customer) {
            $request->session()->forget('portal_customer_id');

            return redirect()->route('portal.login');
        }

        $request->attributes->set('portalCustomer', $customer);
        view()->share('portalCustomer', $customer);

        return $next($request);
    }
}
