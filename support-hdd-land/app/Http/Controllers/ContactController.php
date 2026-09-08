<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function __invoke(Request $request): View
    {
        if (! auth()->check()) {
            $id = (int) $request->session()->get('portal_customer_id');
            if ($id > 0) {
                $customer = Customer::query()->find($id);
                if ($customer) {
                    view()->share('portalCustomer', $customer);
                }
            }
        }

        $layout = auth()->check()
            ? 'layouts.app'
            : (view()->shared('portalCustomer') ? 'layouts.portal' : 'layouts.guest');

        return view('contact', [
            'layout' => $layout,
            'vendor' => [
                'name' => vendor_name(),
                'url' => vendor_url(),
                'host' => vendor_host(),
                'phone' => vendor_phone(),
                'mobile' => vendor_mobile(),
                'tagline' => vendor_tagline(),
            ],
        ]);
    }
}
