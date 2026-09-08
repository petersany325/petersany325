<?php

namespace App\Http\Middleware;

use App\Support\LicenseStatus;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Seller license admin (issue / plans / online reports) is only on the seller site.
 * Customer installs are redirected to their own activation page.
 */
class EnsureSellerLicenseAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (LicenseStatus::isCustomerInstall()) {
            return redirect()->route('licenses.status');
        }

        return $next($request);
    }
}
