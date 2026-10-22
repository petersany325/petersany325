<?php

namespace Plugins\Studio\src\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\StudioHomeConfig;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudioHomeController extends Controller
{
    public function edit(): View
    {
        return view('studio::admin.home', [
            'home' => StudioHomeConfig::get(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        StudioHomeConfig::save($request->all());

        return back()->with('success', 'صفحه اول استودیو ذخیره شد.');
    }
}
