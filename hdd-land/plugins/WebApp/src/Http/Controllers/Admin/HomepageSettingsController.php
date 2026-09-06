<?php

namespace Plugins\WebApp\src\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\HomePageConfig;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Plugins\WebApp\Plugin;

class HomepageSettingsController extends Controller
{
    public function edit(): View
    {
        return view('web-app::admin.homepage-settings', [
            'home' => HomePageConfig::get(),
            'web' => Plugin::settings(),
            'previewDesktop' => url('/'),
            'previewApp' => url('/app'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        HomePageConfig::save($request->all());

        $patch = [
            'hero_enabled' => $request->boolean('hero_enabled'),
            'hero_title' => mb_substr(trim((string) $request->input('hero_title', '')), 0, 90),
            'hero_text' => mb_substr(trim((string) $request->input('hero_text', '')), 0, 200),
            'hero_cta_label' => mb_substr(trim((string) $request->input('hero_cta1_label', '')), 0, 40),
            'hero_cta_url' => mb_substr(trim((string) $request->input('hero_webapp_cta1_url', '/app/shop')), 0, 200) ?: '/app/shop',
            'show_search' => $request->boolean('show_search'),
            'show_categories' => $request->boolean('show_categories'),
            'show_featured' => $request->boolean('show_featured'),
            'show_quick_links' => $request->boolean('show_quick_links'),
            'featured_title' => mb_substr(trim((string) $request->input('featured_title', 'محصولات ویژه')), 0, 80) ?: 'محصولات ویژه',
            'show_install_banner' => $request->boolean('show_install_banner'),
            'install_banner_text' => mb_substr(trim((string) $request->input('install_banner_text', '')), 0, 120),
            'mobile_bottom_nav' => $request->boolean('mobile_bottom_nav'),
        ];

        try {
            Plugin::saveSettings(array_merge(Plugin::settings(), $patch));
        } catch (\Throwable) {
            //
        }

        return back()->with('success', 'تنظیمات صفحه اول و بنر آنلاین ذخیره شد.');
    }
}
