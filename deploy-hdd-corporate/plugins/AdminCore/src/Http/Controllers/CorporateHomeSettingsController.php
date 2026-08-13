<?php

namespace Plugins\AdminCore\src\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Support\CorporateHomeConfig;
use Illuminate\Http\Request;

class CorporateHomeSettingsController extends Controller
{
    private const TABS = [
        'banner' => [
            'banner_kicker', 'banner_title', 'banner_lead',
            'banner_cta_red_label', 'banner_cta_red_url',
            'banner_cta_blue_label', 'banner_cta_blue_url',
            'banner_gate1_title', 'banner_gate1_text',
            'banner_gate2_title', 'banner_gate2_text',
            'banner_orbit_title', 'banner_orbit_subtitle',
            'header_cta_label', 'header_cta_url',
        ],
        'home' => [
            'paths_heading', 'paths_lead',
            'corp_code', 'corp_title', 'corp_text',
            'corp_cta1_label', 'corp_cta1_url', 'corp_cta2_label', 'corp_cta2_url',
            'shop_code', 'shop_title', 'shop_text',
            'shop_cta1_label', 'shop_cta1_url', 'shop_cta2_label', 'shop_cta2_url',
            'sub_paths', 'devices_heading', 'devices_lead', 'devices',
            'cta_heading', 'cta_lead',
            'cta_red_label', 'cta_red_url', 'cta_blue_label', 'cta_blue_url',
        ],
        'footer' => [
            'footer_tagline', 'footer_about',
            'footer_cta_red_label', 'footer_cta_red_url',
            'footer_cta_blue_label', 'footer_cta_blue_url',
            'footer_col1_title', 'footer_col1_links',
            'footer_col2_title', 'footer_col2_links',
            'footer_col3_title', 'footer_col3_links',
            'footer_contact_title', 'footer_hours_title', 'footer_hours_text',
            'footer_copyright', 'footer_bottom_links',
        ],
    ];

    private function loadConfig(): void
    {
        $file = app_path('Support/CorporateHomeConfig.php');
        if (! class_exists(CorporateHomeConfig::class) && is_file($file)) {
            require_once $file;
        }
    }

    public function index()
    {
        $this->loadConfig();
        $tab = request('tab', 'banner');
        if (! isset(self::TABS[$tab])) {
            $tab = 'banner';
        }

        return view('admin-core::corporate-home-settings', [
            's' => CorporateHomeConfig::get(),
            'tab' => $tab,
        ]);
    }

    public function save(Request $request)
    {
        $this->loadConfig();
        $tab = (string) $request->input('tab', 'banner');
        if (! isset(self::TABS[$tab])) {
            $tab = 'banner';
        }

        $current = CorporateHomeConfig::get();
        $patch = [];
        foreach (self::TABS[$tab] as $key) {
            if ($request->has($key)) {
                $patch[$key] = $request->input($key);
            }
        }
        CorporateHomeConfig::save(array_merge($current, $patch));

        return redirect('/admin/corporate-home?tab='.$tab)
            ->with('success', 'تنظیمات ذخیره شد.');
    }
}
