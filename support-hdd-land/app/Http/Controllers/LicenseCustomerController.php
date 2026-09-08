<?php

namespace App\Http\Controllers;

use App\Support\LicenseStatus;
use Illuminate\Http\Request;

/**
 * Customer-install license page: activation details + renew CTA.
 * Seller admin (issue serials / plans) is not available here.
 */
class LicenseCustomerController extends Controller
{
    public function status(Request $request)
    {
        abort_unless($request->user()?->isAdmin(), 403);
        if (! LicenseStatus::isCustomerInstall()) {
            return redirect()->route('licenses.index');
        }

        return view('licenses.status', [
            'license' => LicenseStatus::current(),
        ]);
    }

    public function renewal(Request $request)
    {
        abort_unless($request->user()?->isAdmin(), 403);
        if (! LicenseStatus::isCustomerInstall()) {
            return redirect()->route('licenses.index');
        }

        return view('licenses.renewal', [
            'license' => LicenseStatus::current(),
        ]);
    }
}
