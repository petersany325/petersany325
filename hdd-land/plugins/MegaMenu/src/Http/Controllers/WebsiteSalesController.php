<?php

namespace Plugins\MegaMenu\src\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
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
            'menuBack' => url('/sites/repair-shop/staff-menu'),
            'menuBackLabel' => '→ بازگشت به منوی کارکنان',
        ]);
    }

    public function staffMenu(): View
    {
        MegaMenuPlugin::syncWebsiteSalesMenu();

        return view('mega-menu::storefront.sites.staff-menu', [
            'product' => collect(MegaMenuPlugin::websiteSalesCatalog())->firstWhere('slug', 'repair-shop'),
            'sections' => MegaMenuPlugin::staffMenuTree(),
            'menuKind' => 'staff',
        ]);
    }

    public function customerMenu(): View
    {
        MegaMenuPlugin::syncWebsiteSalesMenu();

        return view('mega-menu::storefront.sites.customer-menu', [
            'product' => collect(MegaMenuPlugin::websiteSalesCatalog())->firstWhere('slug', 'repair-shop'),
            'sections' => MegaMenuPlugin::customerMenuTree(),
            'menuKind' => 'customer',
        ]);
    }

    public function menuItem(string $slug): View|RedirectResponse
    {
        MegaMenuPlugin::syncWebsiteSalesMenu();

        $item = MegaMenuPlugin::findMenuItem($slug);
        if (! $item) {
            abort(404);
        }

        if (! empty($item['guide'])) {
            return redirect()->to(url('/sites/repair-shop/'.$item['guide']));
        }

        $page = MegaMenuPlugin::buildMenuItemPage($item);
        $embed = MegaMenuPlugin::aparatEmbedUrl($page['aparat_url'] ?? '');
        $menuBack = ($page['menu'] ?? '') === 'customer'
            ? url('/sites/repair-shop/customer-menu')
            : url('/sites/repair-shop/staff-menu');
        $menuBackLabel = ($page['menu'] ?? '') === 'customer'
            ? '→ بازگشت به منوی کارتابل مشتری'
            : '→ بازگشت به منوی کارکنان';

        return view('mega-menu::storefront.sites.menu-item', [
            'product' => collect(MegaMenuPlugin::websiteSalesCatalog())->firstWhere('slug', 'repair-shop'),
            'page' => $page,
            'embedUrl' => $embed,
            'menuBack' => $menuBack,
            'menuBackLabel' => $menuBackLabel,
            'blocks' => MegaMenuPlugin::repairGuideBodyBlocks($page['body']),
        ]);
    }
}
