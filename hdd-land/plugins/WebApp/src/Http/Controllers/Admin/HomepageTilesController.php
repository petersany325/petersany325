<?php

namespace Plugins\WebApp\src\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\HomePageConfig;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomepageTilesController extends Controller
{
    public function edit(): View
    {
        $home = HomePageConfig::get();

        return view('web-app::admin.home-options', [
            'home' => $home,
            'tiles' => HomePageConfig::corpTiles($home),
            'preview' => url('/'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        try {
            $payload = $request->only([
                'corp_enabled',
                'corp_title', 'corp_subtitle',
                'corp_1_title', 'corp_1_text', 'corp_1_image', 'corp_1_url',
                'corp_2_title', 'corp_2_text', 'corp_2_image', 'corp_2_url',
                'corp_3_title', 'corp_3_text', 'corp_3_image', 'corp_3_url',
                'corp_4_title', 'corp_4_text', 'corp_4_image', 'corp_4_url',
                'corp_cta_title', 'corp_cta_text', 'corp_cta_label', 'corp_cta_url',
            ]);
            $payload['corp_enabled'] = $request->boolean('corp_enabled');
            $saved = HomePageConfig::save($payload);
            $saved['corp_cards_v'] = 5;
            \App\Support\SettingsStore::set(HomePageConfig::KEY, $saved);

            return back()->with('success', '۴ گزینه صفحه اول ذخیره شد و روی خانه اعمال می‌شود.');
        } catch (\Throwable $e) {
            report($e);

            return back()->withInput()->with('error', 'ذخیره گزینه‌های صفحه اول ناموفق بود.');
        }
    }

    public function apply(): RedirectResponse
    {
        try {
            HomePageConfig::applyDesignedHomeOptions();

            return back()->with('success', '۴ گزینه طراحی‌شده روی صفحه اول اعمال شد.');
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', 'اعمال گزینه‌ها ناموفق بود.');
        }
    }
}
