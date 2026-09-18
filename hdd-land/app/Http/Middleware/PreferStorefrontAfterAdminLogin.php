<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * After admin login, land on the storefront (admin bar) instead of the dark panel.
 * Toolbar «پنل مدیریت» uses /admin?panel=1 to skip this once.
 */
class PreferStorefrontAfterAdminLogin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user || ! method_exists($user, 'isAdmin') || ! $user->isAdmin()) {
            return $next($request);
        }

        if ($request->isMethod('GET') && $request->is('/') && session()->has('admin_land_on_site')) {
            session()->forget('admin_land_on_site');

            return $next($request);
        }

        if (! $request->isMethod('GET') || ! session()->has('admin_land_on_site')) {
            return $next($request);
        }

        $skipPanel = $request->is('admin') && ! $request->boolean('panel');
        $skipCabinet = $request->is('account');
        if (! $skipPanel && ! $skipCabinet) {
            return $next($request);
        }

        session()->forget('admin_land_on_site');

        return redirect()->to(url('/'))
            ->with('success', 'وارد شدید. از نوار بالای صفحه همین صفحه را ویرایش کنید یا به پنل مدیریت بروید.');
    }
}
