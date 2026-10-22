<?php

namespace Plugins\Studio\src\Http\Controllers;

use App\Support\SiteHost;
use App\Support\StudioAccountingCopy;
use App\Support\StudioDesignCopy;
use App\Support\StudioHomeConfig;
use App\Support\StudioProductMenus;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Plugins\Studio\Plugin as StudioPlugin;
use Symfony\Component\HttpFoundation\Response;

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
        return $this->page('design', StudioDesignCopy::seoTitle('design'), [
            'description' => StudioDesignCopy::seoDescription('design'),
            'hero' => StudioDesignCopy::hero(),
            'aboutBlurb' => StudioDesignCopy::aboutBlurb(),
            'services' => StudioDesignCopy::services(),
            'reasons' => StudioDesignCopy::reasons(),
            'steps' => StudioDesignCopy::steps(),
        ]);
    }

    public function designService(string $slug): View|RedirectResponse
    {
        $item = StudioDesignCopy::service($slug);
        if ($item === null) {
            return redirect()->to(url('/design'));
        }

        return $this->page('design-service', $item['seo_title'], [
            'description' => $item['seo_description'],
            'service' => $item,
        ]);
    }

    public function about(): View
    {
        return $this->page('about', StudioDesignCopy::seoTitle('about'), [
            'description' => StudioDesignCopy::seoDescription('about'),
            'aboutPage' => StudioDesignCopy::about(),
            'reasons' => StudioDesignCopy::reasons(),
        ]);
    }

    public function ai(): View
    {
        return $this->page('ai', 'پروژه هوش مصنوعی');
    }

    public function accounting(): View
    {
        $web = StudioProductMenus::webAccountingNav();

        return $this->page('accounting-web', 'حسابداری وب تعمیرکاران | فراز نت آمل', [
            'description' => 'حسابداری تحت وب فقط برای تعمیرکاران و قبض تعمیرگاه. بقیه صنوف حسابداری کامپیوتری با SQL Server 2022 هستند.',
            'webGroups' => $web,
        ]);
    }

    public function computerAccounting(): View
    {
        return $this->page('computer-accounting', 'حسابداری کامپیوتر با SQL Server 2022 | فراز نت آمل', [
            'description' => 'حسابداری کامپیوتری صنوف روی SQL Server 2022: شرکتی، فروشگاهی، سوپرمارکت، پخش، رستوران، تولیدی و پیمانکاری.',
            'groups' => StudioProductMenus::accountingGroups(),
            'industries' => StudioAccountingCopy::industries(),
            'sqlNote' => StudioAccountingCopy::sqlNote(),
        ]);
    }

    public function accountingType(string $slug): View|RedirectResponse
    {
        if (StudioAccountingCopy::industry($slug) !== null) {
            return redirect()->to(url('/computer-accounting/'.$slug), 301);
        }

        return redirect()->to(url('/accounting'));
    }

    public function computerAccountingType(string $slug): View|RedirectResponse
    {
        $industry = StudioAccountingCopy::industry($slug);
        if ($industry === null) {
            return redirect()->to(url('/computer-accounting'));
        }

        return $this->page('accounting-type', $industry['seo_title'], [
            'description' => $industry['seo_description'],
            'industry' => $industry,
            'industries' => StudioAccountingCopy::industries(),
            'sqlNote' => StudioAccountingCopy::sqlNote(),
        ]);
    }

    public function accountingItem(string $slug): View|RedirectResponse
    {
        if (StudioProductMenus::find('accounting', $slug) !== null) {
            return redirect()->to(url('/computer-accounting/m/'.$slug), 301);
        }

        return redirect()->to(url('/accounting'));
    }

    public function computerAccountingItem(string $slug): View|RedirectResponse
    {
        $item = StudioProductMenus::find('accounting', $slug);
        if ($item === null) {
            return redirect()->to(url('/computer-accounting'));
        }

        return $this->page('product-item', $item['title'].' | حسابداری کامپیوتر', [
            'item' => $item,
            'hubUrl' => '/computer-accounting',
            'hubLabel' => 'حسابداری کامپیوتر',
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
        return $this->page('contact', StudioDesignCopy::seoTitle('contact'), [
            'description' => StudioDesignCopy::seoDescription('contact'),
            'contactCopy' => StudioDesignCopy::contact(),
        ]);
    }

    public function sites(): View
    {
        return $this->page('sites', 'نرم‌افزار حسابداری و تعمیرگاه', [
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

    public function manifest(): Response
    {
        $home = StudioHomeConfig::get();
        $icon = url('images/hdd-land-icon-192.png');
        $icon512 = url('images/hdd-land-icon-512.png');
        $payload = [
            'name' => 'فراز نت آمل',
            'short_name' => 'فرازنت',
            'description' => 'طراحی سایت حرفه‌ای، حسابداری وب تعمیرکاران، حسابداری کامپیوتر و نرم‌افزار تعمیرگاه — فراز نت آمل',
            'start_url' => url('/'),
            'scope' => url('/'),
            'display' => 'standalone',
            'orientation' => 'portrait-primary',
            'background_color' => $home['bg'] ?? '#070b18',
            'theme_color' => $home['accent'] ?? '#7c5cff',
            'lang' => 'fa',
            'dir' => 'rtl',
            'icons' => [
                ['src' => $icon, 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => $icon, 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'maskable'],
                ['src' => $icon512, 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => $icon512, 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable'],
            ],
            'shortcuts' => [
                ['name' => 'طراحی سایت', 'url' => url('/design')],
                ['name' => 'حسابداری وب', 'url' => url('/accounting')],
                ['name' => 'حسابداری کامپیوتر', 'url' => url('/computer-accounting')],
                ['name' => 'تعمیرگاه', 'url' => url('/repair-shop')],
                ['name' => 'تماس', 'url' => url('/contact')],
            ],
        ];

        return response(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 200)
            ->header('Content-Type', 'application/manifest+json; charset=UTF-8')
            ->header('Cache-Control', 'no-cache');
    }

    public function serviceWorker(): Response
    {
        $js = <<<'JS'
const CACHE = 'studio-wa-v2';
const PRECACHE = ['/','/design','/accounting','/computer-accounting','/repair-shop','/contact','/about','/css/studio-agency.css'];
self.addEventListener('install', (e) => {
  e.waitUntil(caches.open(CACHE).then((c) => c.addAll(PRECACHE).catch(() => {})).then(() => self.skipWaiting()));
});
self.addEventListener('activate', (e) => {
  e.waitUntil(caches.keys().then((keys) => Promise.all(keys.filter((k) => k !== CACHE).map((k) => caches.delete(k)))).then(() => self.clients.claim()));
});
self.addEventListener('fetch', (e) => {
  if (e.request.method !== 'GET') return;
  const url = new URL(e.request.url);
  if (url.origin !== self.location.origin) return;
  e.respondWith(fetch(e.request).then((res) => {
    const copy = res.clone();
    caches.open(CACHE).then((c) => c.put(e.request, copy)).catch(() => {});
    return res;
  }).catch(() => caches.match(e.request).then((hit) => hit || caches.match('/'))));
});
JS;

        return response($js, 200)
            ->header('Content-Type', 'application/javascript; charset=UTF-8')
            ->header('Service-Worker-Allowed', '/')
            ->header('Cache-Control', 'no-cache');
    }

    public function dismissBanner(Request $request): Response
    {
        $request->session()->put('studio_webapp_banner_dismissed', true);

        return response()->json(['ok' => true]);
    }

    /** @param  array<string, mixed>  $extra */
    protected function page(string $slug, string $title, array $extra = []): View
    {
        StudioPlugin::ensureBooted();

        return view('studio::page', array_merge([
            'slug' => $slug,
            'title' => $title,
            'description' => $extra['description'] ?? null,
            'menu' => SiteHost::studioMenu(),
            'receiptUrl' => SiteHost::receiptUrl(),
            'shopUrl' => SiteHost::shopUrl('/'),
        ], $extra));
    }
}
