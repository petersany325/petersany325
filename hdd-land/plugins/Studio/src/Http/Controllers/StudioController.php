<?php

namespace Plugins\Studio\src\Http\Controllers;

use App\Support\SiteHost;
use Illuminate\Contracts\View\View;
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
        return $this->page('accounting', 'نرم‌افزار پیشرفته حسابداری هوش مصنوعی');
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

    protected function page(string $slug, string $title): View
    {
        StudioPlugin::ensureBooted();

        return view('studio::page', [
            'slug' => $slug,
            'title' => $title,
            'menu' => SiteHost::studioMenu(),
            'receiptUrl' => SiteHost::receiptUrl(),
            'shopUrl' => SiteHost::shopUrl('/'),
        ]);
    }
}
