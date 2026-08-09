<?php

namespace Plugins\WebApp\src\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Plugins\WebApp\Plugin;
use Plugins\WebApp\src\Support\SiteSync;

class WebAppController extends Controller
{
    public function home(): View|RedirectResponse
    {
        $s = $this->requireEnabled();
        if ($s instanceof RedirectResponse) {
            return $s;
        }

        return view('web-app::pages.home', $this->viewData($s, [
            'tab' => 'home',
            'title' => $s['app_name'],
            'categories' => $this->categories((int) ($s['categories_limit'] ?? 10)),
            'products' => $this->products((int) ($s['products_limit'] ?? 16)),
            'mobileHero' => SiteSync::heroBanner(),
        ]));
    }

    public function shop(Request $request): View|RedirectResponse
    {
        $s = $this->requireEnabled();
        if ($s instanceof RedirectResponse) {
            return $s;
        }

        $q = trim((string) $request->input('q', ''));
        $cat = trim((string) $request->input('cat', ''));
        $partType = trim((string) $request->input('part_type', ''));
        $brand = trim((string) $request->input('brand', ''));
        $products = $this->products(
            (int) ($s['shop_per_page'] ?? 24),
            $q,
            $cat !== '' ? $cat : null,
            0,
            0,
            $partType !== '' ? $partType : null,
            $brand !== '' ? $brand : null,
        );

        return view('web-app::pages.shop', $this->viewData($s, [
            'tab' => 'shop',
            'title' => $s['shop_title'] ?? 'فروشگاه',
            'categories' => $this->categories((int) ($s['categories_limit'] ?? 10)),
            'products' => $products,
            'q' => $q,
            'cat' => $cat,
        ]));
    }

    public function product(string $slug): View|RedirectResponse
    {
        $s = $this->requireEnabled();
        if ($s instanceof RedirectResponse) {
            return $s;
        }

        $product = $this->findProduct($slug);
        if (! $product) {
            return redirect('/app/shop')->with('error', 'محصول یافت نشد.');
        }

        return view('web-app::pages.product', $this->viewData($s, [
            'tab' => 'shop',
            'title' => $product->name ?? 'محصول',
            'product' => $product,
            'related' => $this->products(6, '', null, (int) ($product->category_id ?? 0), (int) $product->id),
        ]));
    }

    public function cart(): View|RedirectResponse
    {
        $s = $this->requireEnabled();
        if ($s instanceof RedirectResponse) {
            return $s;
        }

        $detailed = ['lines' => [], 'subtotal' => 0];
        try {
            if (class_exists(\Plugins\CartCheckout\src\Cart::class)) {
                $detailed = \Plugins\CartCheckout\src\Cart::detailed();
            }
        } catch (\Throwable) {
            //
        }

        return view('web-app::pages.cart', $this->viewData($s, [
            'tab' => 'cart',
            'title' => $s['nav_cart_label'] ?? 'سبد',
            'lines' => $detailed['lines'] ?? [],
            'subtotal' => (int) ($detailed['subtotal'] ?? 0),
        ]));
    }

    public function account(): View|RedirectResponse
    {
        $s = $this->requireEnabled();
        if ($s instanceof RedirectResponse) {
            return $s;
        }

        $user = auth()->user();
        $orders = collect();
        if ($user && Schema::hasTable('orders')) {
            try {
                $orders = DB::table('orders')
                    ->where('user_id', $user->id)
                    ->orderByDesc('id')
                    ->limit(5)
                    ->get();
            } catch (\Throwable) {
                //
            }
        }

        return view('web-app::pages.account', $this->viewData($s, [
            'tab' => 'account',
            'title' => $s['nav_account_label'] ?? 'حساب',
            'user' => $user,
            'orders' => $orders,
        ]));
    }

    public function addToCart(Request $request): RedirectResponse
    {
        $s = Plugin::settings();
        if (empty($s['enabled'])) {
            return redirect('/');
        }

        $productId = (int) $request->input('product_id');
        $qty = max(1, (int) $request->input('qty', 1));
        $buyNow = $request->boolean('buy_now');

        try {
            if (class_exists(\Plugins\CartCheckout\src\Cart::class) && $productId > 0) {
                \Plugins\CartCheckout\src\Cart::add($productId, $qty);
            }
        } catch (\Throwable $e) {
            return back()->with('error', 'افزودن به سبد ممکن نشد.');
        }

        if ($buyNow) {
            return redirect('/checkout');
        }

        return redirect('/app/cart')->with('success', 'به سبد اضافه شد.');
    }

    public function updateCart(Request $request): RedirectResponse
    {
        if (empty(Plugin::settings()['enabled'])) {
            return redirect('/');
        }

        $key = (string) $request->input('key');
        $qty = (int) $request->input('qty', 1);

        try {
            if (class_exists(\Plugins\CartCheckout\src\Cart::class)) {
                \Plugins\CartCheckout\src\Cart::update($key, $qty);
            }
        } catch (\Throwable) {
            return back()->with('error', 'بروزرسانی سبد ناموفق بود.');
        }

        return redirect('/app/cart');
    }

    public function removeCart(string $key): RedirectResponse
    {
        if (empty(Plugin::settings()['enabled'])) {
            return redirect('/');
        }

        try {
            if (class_exists(\Plugins\CartCheckout\src\Cart::class)) {
                \Plugins\CartCheckout\src\Cart::remove($key);
            }
        } catch (\Throwable) {
            //
        }

        return redirect('/app/cart')->with('success', 'حذف شد.');
    }

    public function manifest(): Response
    {
        $s = Plugin::settings();
        if (empty($s['enabled'])) {
            return response('{}', 404)->header('Content-Type', 'application/manifest+json');
        }

        $icon192 = Plugin::iconUrl($s['icon_192'] ?? null, 'images/hdd-land-icon-192.png');
        $icon512 = Plugin::iconUrl($s['icon_512'] ?? null, 'images/hdd-land-icon-512.png');

        $payload = [
            'name' => $s['app_name'],
            'short_name' => $s['short_name'],
            'description' => $s['description'],
            'start_url' => url($s['start_url'] ?: '/app'),
            'scope' => url('/'),
            'display' => $s['display'] ?: 'standalone',
            'orientation' => $s['orientation'] ?: 'portrait-primary',
            'background_color' => $s['background_color'] ?: '#1a1d23',
            'theme_color' => $s['theme_color'] ?: '#e23d12',
            'lang' => 'fa',
            'dir' => 'rtl',
            'prefer_related_applications' => false,
            'icons' => [
                ['src' => $icon192, 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => $icon192, 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'maskable'],
                ['src' => $icon512, 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => $icon512, 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable'],
            ],
            'shortcuts' => [
                ['name' => $s['nav_shop_label'] ?? 'فروشگاه', 'url' => url('/app/shop'), 'icons' => [['src' => $icon192, 'sizes' => '192x192']]],
                ['name' => $s['nav_cart_label'] ?? 'سبد', 'url' => url('/app/cart'), 'icons' => [['src' => $icon192, 'sizes' => '192x192']]],
            ],
        ];

        return response(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT), 200)
            ->header('Content-Type', 'application/manifest+json; charset=UTF-8')
            ->header('Cache-Control', 'no-cache');
    }

    public function serviceWorker(): Response
    {
        $s = Plugin::settings();
        $offline = ! empty($s['offline_cache']);
        $version = 'webapp-v2-'.substr(md5(json_encode([
            $s['app_name'] ?? '', $s['theme_color'] ?? '', $s['enabled'] ?? false, 'drawer-sub-v12',
        ])), 0, 8);
        $offlineJs = $this->jsBool($offline);

        $js = <<<JS
const CACHE = '{$version}';
const OFFLINE = {$offlineJs};
const PRECACHE = ['/app','/app/shop','/app/cart','/app/account','/manifest.webmanifest','/css/webapp.css','/js/webapp.js'];
self.addEventListener('install', (e) => {
  if (!OFFLINE) { self.skipWaiting(); return; }
  e.waitUntil(caches.open(CACHE).then((c) => c.addAll(PRECACHE).catch(() => {})).then(() => self.skipWaiting()));
});
self.addEventListener('activate', (e) => {
  e.waitUntil(caches.keys().then((keys) => Promise.all(keys.filter((k) => k !== CACHE).map((k) => caches.delete(k)))).then(() => self.clients.claim()));
});
self.addEventListener('fetch', (e) => {
  if (!OFFLINE || e.request.method !== 'GET') return;
  const url = new URL(e.request.url);
  if (url.origin !== self.location.origin) return;
  e.respondWith(fetch(e.request).then((res) => {
    const copy = res.clone();
    caches.open(CACHE).then((c) => c.put(e.request, copy)).catch(() => {});
    return res;
  }).catch(() => caches.match(e.request).then((hit) => hit || caches.match('/app'))));
});
JS;

        return response($js, 200)
            ->header('Content-Type', 'application/javascript; charset=UTF-8')
            ->header('Service-Worker-Allowed', '/')
            ->header('Cache-Control', 'no-cache');
    }

    public function icon(int $size)
    {
        $size = in_array($size, [192, 512], true) ? $size : 192;
        $file = public_path('images/hdd-land-icon-'.$size.'.png');
        if (! is_file($file) || filesize($file) <= 0) {
            $file = public_path('images/hdd-land-icon-192.png');
        }
        if (is_file($file) && filesize($file) > 0) {
            return response()->file($file, [
                'Content-Type' => 'image/png',
                'Cache-Control' => 'public, max-age=86400',
            ]);
        }

        return redirect()->to(asset('images/hdd-land-icon-192.png'));
    }

    public function dismissBanner(Request $request): Response
    {
        $request->session()->put('webapp_banner_dismissed', true);

        return response()->json(['ok' => true]);
    }

    /** @return array<string,mixed>|RedirectResponse */
    protected function requireEnabled(): array|RedirectResponse
    {
        $s = Plugin::settings();
        if (empty($s['enabled'])) {
            return redirect('/');
        }

        $brand = SiteSync::resolveBrand($s);
        $s['app_name'] = $brand['app_name'];
        $s['theme_color'] = $brand['theme_color'];

        return $s;
    }

    /**
     * @param  array<string,mixed>  $s
     * @param  array<string,mixed>  $extra
     * @return array<string,mixed>
     */
    protected function viewData(array $s, array $extra = []): array
    {
        return array_merge([
            's' => $s,
            'cartCount' => $this->cartCount(),
            'siteMenu' => SiteSync::resolveMenu($s),
            'quickLinks' => SiteSync::resolveQuickLinks($s),
            'waFooter' => SiteSync::resolveFooter($s),
            'drawerMenu' => Plugin::resolveDrawerMenu($s),
        ], $extra);
    }

    protected function cartCount(): int
    {
        try {
            if (class_exists(\Plugins\CartCheckout\src\Cart::class)) {
                return (int) \Plugins\CartCheckout\src\Cart::count();
            }
        } catch (\Throwable) {
            //
        }

        return 0;
    }

    protected function categories(int $limit)
    {
        try {
            if (! Schema::hasTable('categories') || $limit <= 0) {
                return collect();
            }
            $q = DB::table('categories');
            if (Schema::hasColumn('categories', 'is_active')) {
                $q->where('is_active', 1);
            }
            if (Schema::hasColumn('categories', 'sort_order')) {
                $q->orderBy('sort_order');
            }

            return $q->orderBy('id')->limit($limit)->get();
        } catch (\Throwable) {
            return collect();
        }
    }

    protected function products(
        int $limit,
        string $search = '',
        ?string $catSlug = null,
        int $categoryId = 0,
        int $excludeId = 0,
        ?string $partType = null,
        ?string $brand = null,
    ) {
        try {
            if (! Schema::hasTable('products')) {
                return collect();
            }
            $q = DB::table('products');
            if (Schema::hasColumn('products', 'status')) {
                $q->where('status', 'publish');
            } elseif (Schema::hasColumn('products', 'is_active')) {
                $q->where('is_active', 1);
            }
            if ($search !== '') {
                $q->where(function ($w) use ($search) {
                    $w->where('name', 'like', '%'.$search.'%');
                    if (Schema::hasColumn('products', 'sku')) {
                        $w->orWhere('sku', 'like', '%'.$search.'%');
                    }
                    if (Schema::hasColumn('products', 'brand')) {
                        $w->orWhere('brand', 'like', '%'.$search.'%');
                    }
                });
            }
            if ($catSlug && Schema::hasTable('categories')) {
                $cat = DB::table('categories')->where('slug', $catSlug)->first();
                if ($cat) {
                    $ids = [$cat->id];
                    $childIds = DB::table('categories')->where('parent_id', $cat->id)->pluck('id')->all();
                    $q->whereIn('category_id', array_values(array_unique(array_merge($ids, $childIds))));
                }
            }
            if ($categoryId > 0) {
                $q->where('category_id', $categoryId);
            }
            if ($partType && Schema::hasColumn('products', 'part_type')) {
                $q->where('part_type', $partType);
            }
            if ($brand && Schema::hasColumn('products', 'brand')) {
                $q->where('brand', 'like', '%'.$brand.'%');
            }
            if ($excludeId > 0) {
                $q->where('id', '!=', $excludeId);
            }

            return $q->orderByDesc('id')->limit($limit)->get();
        } catch (\Throwable) {
            return collect();
        }
    }

    protected function findProduct(string $slug): ?object
    {
        try {
            if (! Schema::hasTable('products')) {
                return null;
            }
            $q = DB::table('products')->where('slug', $slug);
            if (Schema::hasColumn('products', 'status')) {
                $q->where('status', 'publish');
            }

            return $q->first();
        } catch (\Throwable) {
            return null;
        }
    }

    protected function absoluteIconUrl(string $path): string
    {
        $path = trim($path);
        if ($path === '') {
            return asset('images/hdd-land-icon-192.png');
        }
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return url('/'.ltrim($path, '/'));
    }

    protected function assetExists(string $url): bool
    {
        $path = parse_url($url, PHP_URL_PATH);
        if (! is_string($path) || $path === '') {
            return false;
        }
        $file = public_path(ltrim($path, '/'));

        return is_file($file) && filesize($file) > 0;
    }

    protected function jsBool(bool $v): string
    {
        return $v ? 'true' : 'false';
    }
}
