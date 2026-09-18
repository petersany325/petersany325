<?php

namespace Plugins\AboutDesk\src\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Plugins\AboutDesk\src\Support\AboutCopy;

class AboutController extends Controller
{
    public function edit()
    {
        return view('about-desk::admin', [
            'copy' => AboutCopy::get(),
        ]);
    }

    public function save(Request $request)
    {
        AboutCopy::save($request->all());

        return redirect()
            ->to(url('/admin/about-page'))
            ->with('success', 'متن صفحه درباره ما ذخیره شد.');
    }
}
