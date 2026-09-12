<?php

namespace App\Http\Controllers;

use App\Support\LicenseStatus;
use App\Support\SellerContact;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(LicenseStatus::isCustomerSite(), 404);

        return view('contact.index', [
            'contact' => SellerContact::all(),
        ]);
    }
}
