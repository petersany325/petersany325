<?php

namespace Plugins\ServicesDesk\src\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Plugins\ServicesDesk\src\Support\ServicesCopy;

class ServicesController extends Controller
{
    public function edit()
    {
        return view('services-desk::admin', [
            'copy' => ServicesCopy::get(),
        ]);
    }

    public function save(Request $request)
    {
        ServicesCopy::save($request->all());

        return redirect()
            ->to(url('/admin/services-page'))
            ->with('success', 'متن و تنظیمات صفحه خدمات سازمانی ذخیره شد.');
    }
}
