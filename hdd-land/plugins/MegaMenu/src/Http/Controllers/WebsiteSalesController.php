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

        return view('mega-menu::storefront.sites.show', [
            'item' => $item,
            'items' => MegaMenuPlugin::websiteSalesCatalog(),
        ]);
    }
}
