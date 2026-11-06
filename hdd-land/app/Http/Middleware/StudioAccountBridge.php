<?php

namespace App\Http\Middleware;

use App\Support\SiteHost;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Plugins\Studio\src\Http\Controllers\StudioCustomerDeskController;
use Symfony\Component\HttpFoundation\Response;

/**
 * On the studio host, /account* is the customer desk (tickets, buy, sell, support).
 * Guests go to SMS login, not the broken shop /login page.
 */
class StudioAccountBridge
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            if (SiteHost::isReceiptHost($request) || SiteHost::isShop($request)) {
                return $next($request);
            }
            $path = '/'.ltrim($request->path(), '/');
            if ($path !== '/account' && ! str_starts_with($path, '/account/')) {
                return $next($request);
            }
            if (! Auth::check()) {
                return redirect()->to(url('/auth/customer'))->with('error', 'برای کارتابل مشتری وارد شوید.');
            }
            $ours = [
                '/account', '/account/buy', '/account/sell', '/account/shop',
                '/account/orders', '/account/invoices', '/account/tickets',
                '/account/support', '/account/wallet', '/account/serials', '/account/prices',
            ];
            if (! in_array($path, $ours, true)) {
                return $next($request);
            }

            return app(StudioCustomerDeskController::class)->dispatchPath($request, $path);
        } catch (\Throwable) {
            return $next($request);
        }
    }
}
