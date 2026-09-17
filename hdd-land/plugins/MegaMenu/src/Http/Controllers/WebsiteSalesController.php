<?php

namespace Plugins\MegaMenu\src\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Plugins\MegaMenu\Plugin as MegaMenuPlugin;

class WebsiteSalesController
{
    public function index(): View
    {
        MegaMenuPlugin::syncWebsiteSalesMenu();

        return view('mega-menu::storefront.sites.index', [
            'items' => MegaMenuPlugin::websiteSalesCatalog(),
        ]);
    }

    public function show(Request $request, string $slug): View
    {
        MegaMenuPlugin::syncWebsiteSalesMenu();

        $item = collect(MegaMenuPlugin::websiteSalesCatalog())->firstWhere('slug', $slug);
        if (! $item) {
            abort(404);
        }

        $guides = [];
        if (! empty($item['has_guides'])) {
            $guides = MegaMenuPlugin::repairShopGuides(true);
        }

        return view('mega-menu::storefront.sites.show', [
            'item' => $item,
            'items' => MegaMenuPlugin::websiteSalesCatalog(),
            'guides' => $guides,
        ]);
    }

    public function repairGuide(string $guide): View
    {
        MegaMenuPlugin::syncWebsiteSalesMenu();

        $current = MegaMenuPlugin::repairShopGuide($guide);
        if (! $current || empty($current['is_active'])) {
            abort(404);
        }

        $all = MegaMenuPlugin::repairShopGuides(true);
        $embed = MegaMenuPlugin::aparatEmbedUrl($current['aparat_url'] ?? '');

        return view('mega-menu::storefront.sites.guide', [
            'product' => collect(MegaMenuPlugin::websiteSalesCatalog())->firstWhere('slug', 'repair-shop'),
            'guide' => $current,
            'guides' => $all,
            'embedUrl' => $embed,
        ]);
    }

    /** نمونهٔ نمایشی فهرست متنی منوی کارکنان */
    public function staffMenu(): View
    {
        MegaMenuPlugin::syncWebsiteSalesMenu();

        return view('mega-menu::storefront.sites.staff-menu', [
            'product' => collect(MegaMenuPlugin::websiteSalesCatalog())->firstWhere('slug', 'repair-shop'),
            'sections' => MegaMenuPlugin::staffMenuSample(),
            'guides' => MegaMenuPlugin::repairShopGuides(true),
        ]);
    }
}
