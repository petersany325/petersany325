<?php

namespace Plugins\WebApp\src\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\HomePageConfig;
use App\Support\SettingsStore;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Plugins\WebApp\Plugin;

/**
 * Modern Hero Studio — replaces fragile Revolution / ThemeBuilder banner admin.
 */
class HeroStudioController extends Controller
{
    public function edit(): View
    {
        return view('web-app::admin.hero-studio', [
            'home' => HomePageConfig::get(),
            'previewDesktop' => url('/'),
            'previewApp' => url('/app'),
            'useLegacyBanner' => SettingsStore::toBool(SettingsStore::get('hero_use_legacy_banner', false), false),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        try {
            SettingsStore::set('hero_use_legacy_banner', $request->boolean('use_legacy_banner') ? 1 : 0);
            $saved = HomePageConfig::save($request->all());

            try {
                Plugin::saveSettings(array_merge(Plugin::settings(), [
                    'hero_enabled' => ! empty($saved['hero_enabled']),
                    'hero_title' => (string) ($saved['hero_title'] ?? ''),
                    'hero_text' => (string) ($saved['hero_text'] ?? ''),
                    'hero_cta_label' => (string) ($saved['hero_cta1_label'] ?? ''),
                    'hero_cta_url' => (string) ($saved['hero_webapp_cta1_url'] ?? '/app/shop'),
                ]));
            } catch (\Throwable) {
                //
            }

            return back()->with('success', 'هیرو صفحه اول ذخیره شد.');
        } catch (\Throwable $e) {
            report($e);

            return back()->withInput()->with('error', 'ذخیره انجام نشد. لطفاً دوباره تلاش کنید.');
        }
    }
}
