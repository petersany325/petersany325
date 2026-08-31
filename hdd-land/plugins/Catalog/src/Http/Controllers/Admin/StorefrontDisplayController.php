<?php

namespace Plugins\Catalog\src\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Plugins\Catalog\src\Support\StorefrontDisplaySettings;

class StorefrontDisplayController extends Controller
{
    public function edit(): View
    {
        return view('catalog::admin.storefront-display-settings', [
            's' => StorefrontDisplaySettings::get(),
            'brandPreview' => StorefrontDisplaySettings::brandLabel(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        StorefrontDisplaySettings::save($request->all());

        return back()->with('success', 'تنظیمات نمایش صفحه محصول ذخیره شد.');
    }
}
