<?php

namespace Plugins\Studio\src\Http\Controllers;

use App\Support\SiteHost;
use App\Support\StudioProductMenus;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Plugins\Studio\Plugin as StudioPlugin;

class StudioController
{
    public function home(): View
    {
        $homeTitle = 'تخصصی طراحی سایت و نرم‌افزار';
        if (class_exists(\App\Support\StudioHomeConfig::class)) {
            $cfg = \App\Support\StudioHomeConfig::get();
            $homeTitle = trim((string) ($cfg['hero_title'] ?? '')) ?: trim((string) ($cfg['tagline'] ?? '')) ?: $homeTitle;
        }

        return $this->page('home', $homeTitle);
    }

    public function design(): View
    {
        return $this->page('design', 'طراحی سایت');
    }

    public function ai(): View
    {
        return $this->page('ai', 'پروژه هوش مصنوعی');
    }

    public function accounting(): View
    {
        return $this->page('accounting', 'حسابداری وب', [
            'groups' => StudioProductMenus::accountingGroups(),
        ]);
    }

    public function accountingItem(string $slug): View|RedirectResponse
    {
        $item = StudioProductMenus::find('accounting', $slug);
        if ($item === null) {
            return redirect()->to(url('/accounting'));
        }

        return $this->page('product-item', $item['title'].' | حسابداری وب', [
            'item' => $item,
            'hubUrl' => '/accounting',
            'hubLabel' => 'حسابداری وب',
            'groups' => StudioProductMenus::accountingGroups(),
        ]);
    }

    public function projects(): View
    {
        return $this->page('projects', 'پروژه‌های سایت');
    }

    public function receiptSoftware(): View
    {
        return $this->page('receipt', 'نرم‌افزار قبض تحت وب');
    }

    public function contact(): View
    {
        return $this->page('contact', 'تماس و مشاوره');
    }

    public function sites(): View
    {
        return $this->page('sites', 'نرم‌افزار حسابداری وب و تعمیرگاه', [
            'catalog' => StudioProductMenus::catalog(),
        ]);
    }

    public function sitesSlug(string $slug): View|RedirectResponse
    {
        if ($slug === 'repair-shop') {
            return $this->repairShop();
        }
        if ($slug === 'accounting') {
            return redirect()->to(url('/accounting'));
        }

        return $this->sites();
    }

    public function repairShop(): View
    {
        return $this->page('repair', 'سایت مدیریت تعمیرگاه', [
            'staff' => StudioProductMenus::repairStaffTree(),
            'customer' => StudioProductMenus::repairCustomerTree(),
        ]);
    }

    public function repairStaffMenu(): View
    {
        return $this->page('repair-staff', 'منوی کارکنان تعمیرگاه', [
            'staff' => StudioProductMenus::repairStaffTree(),
        ]);
    }

    public function repairCustomerMenu(): View
    {
        return $this->page('repair-customer', 'منوی کارتابل مشتری', [
            'customer' => StudioProductMenus::repairCustomerTree(),
        ]);
    }

    public function repairItem(string $slug): View|RedirectResponse
    {
        $item = StudioProductMenus::find('repair', $slug);
        if ($item === null) {
            return redirect()->to(url('/repair-shop'));
        }

        return $this->page('product-item', $item['title'].' | تعمیرگاه', [
            'item' => $item,
            'hubUrl' => ($item['menu'] ?? '') === 'customer' ? '/repair-shop/customer-menu' : '/repair-shop/staff-menu',
            'hubLabel' => ($item['menu'] ?? '') === 'customer' ? 'منوی کارتابل مشتری' : 'منوی کارکنان',
            'staff' => StudioProductMenus::repairStaffTree(),
            'customer' => StudioProductMenus::repairCustomerTree(),
        ]);
    }

    /** @param  array<string, mixed>  $extra */
    protected function page(string $slug, string $title, array $extra = []): View
    {
        StudioPlugin::ensureBooted();

        return view('studio::page', array_merge([
            'slug' => $slug,
            'title' => $title,
            'menu' => SiteHost::studioMenu(),
            'receiptUrl' => SiteHost::receiptUrl(),
            'shopUrl' => SiteHost::shopUrl('/'),
        ], $extra));
    }
}
