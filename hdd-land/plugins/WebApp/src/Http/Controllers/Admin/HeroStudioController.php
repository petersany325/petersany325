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
            'useLegacyBanner' => false,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        try {
            // Legacy Revolution banner stays permanently off.
            SettingsStore::set('hero_use_legacy_banner', 0);

            // Only patch hero fields — never wipe trust/edu/about/corp blocks.
            $current = HomePageConfig::get();
            $heroPatch = $request->only([
                'hero_kicker', 'hero_title', 'hero_title_em', 'hero_text', 'hero_image',
                'hero_cta1_label', 'hero_cta1_url', 'hero_cta2_label', 'hero_cta2_url',
                'hero_webapp_cta1_url', 'hero_layout', 'hero_font',
                'hero_height', 'hero_radius', 'hero_title_size',
                'hero_title_color', 'hero_cta1_bg',
            ]);
            $payload = array_merge($current, $heroPatch);
            $payload['hero_enabled'] = $request->boolean('hero_enabled');
            $saved = HomePageConfig::save($payload);

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
