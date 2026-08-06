<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function edit(): View
    {
        $keys = ['site_name', 'site_tagline', 'phone', 'address', 'hours', 'about_title', 'about_text', 'hero_lead'];
        $settings = [];
        foreach ($keys as $key) {
            $settings[$key] = Setting::get($key, '');
        }

        return view('admin.settings.edit', compact('settings'));
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'site_name' => ['required', 'string', 'max:160'],
            'site_tagline' => ['nullable', 'string', 'max:160'],
            'phone' => ['nullable', 'string', 'max:60'],
            'address' => ['nullable', 'string', 'max:255'],
            'hours' => ['nullable', 'string', 'max:120'],
            'about_title' => ['nullable', 'string', 'max:255'],
            'about_text' => ['nullable', 'string', 'max:5000'],
            'hero_lead' => ['nullable', 'string', 'max:1000'],
        ]);

        Setting::many($data);

        return back()->with('success', 'تنظیمات ذخیره شد.');
    }
}
