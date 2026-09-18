<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

/**
 * WordPress-style frontend admin bar: contextual edit links for the current page.
 */
class AdminToolbar
{
    /** @return array<string, mixed>|null */
    public static function current(?Request $request = null): ?array
    {
        $request ??= request();
        $user = $request->user();
        if (! $user) {
            return null;
        }

        $isAdmin = method_exists($user, 'isAdmin') && $user->isAdmin();
        $isStaff = method_exists($user, 'isStaff') && $user->isStaff();
        if (! $isAdmin && ! $isStaff) {
            return null;
        }

        $path = trim($request->path(), '/');
        if ($path === 'admin' || str_starts_with($path, 'admin/') || $path === 'staff' || str_starts_with($path, 'staff/')) {
            return null;
        }
        $can = static fn (string $perm) => $isAdmin || $user->hasStaffPermission($perm);

        $ctx = static::context($request, $path, $can);
        $design = array_values(array_filter([
            $can('site.homepage') ? ['label' => 'بنر و هیرو', 'url' => url('/admin/hero-studio')] : null,
            $can('site.homepage') ? ['label' => 'محتوای صفحه اول', 'url' => url('/admin/homepage-settings')] : null,
            $can('site.mega_menu') ? ['label' => 'مگامنو و هدر', 'url' => url('/admin/mega-menu')] : null,
            $can('site.footer') ? ['label' => 'فوتر', 'url' => url('/admin/footer-settings')] : null,
            $can('site.webapp') ? ['label' => 'وب‌اپ / PWA', 'url' => url('/admin/web-app')] : null,
            $can('site.page_builder') ? ['label' => 'صفحه‌ساز', 'url' => url('/admin/page-builder')] : null,
            $can('site.shop_settings') ? ['label' => 'تنظیمات فروشگاه', 'url' => url('/admin/settings')] : null,
        ]));

        $new = array_values(array_filter([
            $can('products.create') ? ['label' => 'محصول جدید', 'url' => url('/admin/products/create')] : null,
            $isAdmin ? ['label' => 'کارمند جدید', 'url' => url('/admin/staff/create')] : null,
            $can('site.page_builder') ? ['label' => 'صفحه جدید', 'url' => url('/admin/page-builder')] : null,
        ]));

        if (! $ctx['primary'] && $ctx['links'] === [] && $design === [] && ! $isAdmin) {
            return null;
        }

        return [
            'user_name' => trim((string) ($user->name ?: $user->username ?: 'مدیر')),
            'is_admin' => $isAdmin,
            'context_label' => $ctx['label'],
            'primary' => $ctx['primary'],
            'links' => $ctx['links'],
            'design' => $design,
            'new' => $new,
            'panel_url' => $isAdmin ? url('/admin?panel=1') : url('/staff'),
            'panel_label' => $isAdmin ? 'پنل مدیریت' : 'پنل کارمند',
            'staff_url' => url('/staff'),
            'account_url' => url('/account'),
            'home_url' => url('/'),
        ];
    }

    /**
     * @param  callable(string):bool  $can
     * @return array{label:string,primary:?array{label:string,url:string},links:list<array{label:string,url:string}>}
     */
    protected static function context(Request $request, string $path, callable $can): array
    {
        $label = 'سایت';
        $primary = null;
        $links = [];

        $add = static function (?array $item) use (&$links): void {
            if ($item) {
                $links[] = $item;
            }
        };

        if ($path === '' || $path === '/') {
            $label = 'خانه';
            if ($can('site.homepage')) {
                $primary = ['label' => 'ویرایش بنر این صفحه', 'url' => url('/admin/hero-studio')];
                $add(['label' => 'محتوای صفحه اول', 'url' => url('/admin/homepage-settings')]);
            }
            if ($can('site.mega_menu')) {
                $add(['label' => 'منوی بالا', 'url' => url('/admin/mega-menu')]);
            }
            if ($can('site.footer')) {
                $add(['label' => 'فوتر', 'url' => url('/admin/footer-settings')]);
            }

            return compact('label', 'primary', 'links');
        }

        if ($path === 'app' || $path === 'app/') {
            $label = 'خانه وب‌اپ';
            if ($can('site.webapp')) {
                $primary = ['label' => 'ویرایش وب‌اپ', 'url' => url('/admin/web-app')];
            }
            if ($can('site.homepage')) {
                $add(['label' => 'بنر مشترک سایت', 'url' => url('/admin/hero-studio')]);
            }

            return compact('label', 'primary', 'links');
        }

        if ($path === 'app/shop' || $path === 'products') {
            $label = 'کاتالوگ';
            if ($can('products.view') || $can('products.edit')) {
                $primary = ['label' => 'ویرایش محصولات', 'url' => url('/admin/products')];
            }
            $add($can('products.edit') ? ['label' => 'دسته‌بندی‌ها', 'url' => url('/admin/categories')] : null);

            return compact('label', 'primary', 'links');
        }

        if (preg_match('#^(?:products|app/p|app/product)/([^/]+)$#', $path, $m)) {
            $product = static::findProduct($m[1]);
            $label = $product ? 'محصول: '.$product->name : 'محصول';
            if ($product && $can('products.edit')) {
                $primary = ['label' => 'ویرایش این محصول', 'url' => url('/admin/products/'.$product->id.'/edit')];
            } elseif ($can('products.view')) {
                $primary = ['label' => 'فهرست محصولات', 'url' => url('/admin/products')];
            }
            $add($can('products.edit') ? ['label' => 'همه محصولات', 'url' => url('/admin/products')] : null);

            return compact('label', 'primary', 'links');
        }

        if ($path === 'sites' || str_starts_with($path, 'sites/')) {
            $label = 'فروش سایت';
            if ($can('site.mega_menu')) {
                $primary = ['label' => 'ویرایش متن این بخش', 'url' => url('/admin/mega-menu#mm-repair-guides')];
                $add(['label' => 'مگامنو «طراحی و فروش سایت»', 'url' => url('/admin/mega-menu')]);
            }
            if (preg_match('#^sites/repair-shop/(referral|cost-approval|staff|trainee)$#', $path, $g) && $can('site.mega_menu')) {
                $guide = class_exists(\Plugins\MegaMenu\Plugin::class) ? \Plugins\MegaMenu\Plugin::repairShopGuide($g[1]) : null;
                $label = $guide['title'] ?? 'کارتابل';
                $primary = ['label' => 'ویرایش این کارتابل', 'url' => url('/admin/mega-menu#mm-repair-guides')];
            }

            return compact('label', 'primary', 'links');
        }

        if ($path === 'warranty-register' || str_starts_with($path, 'warranty-register/')) {
            $label = 'ثبت گارانتی';
            if ($can('serials')) {
                $primary = ['label' => 'ویرایش این صفحه', 'url' => url('/admin/warranty-register')];
                $add(['label' => 'درخواست‌های پوشش', 'url' => url('/admin/warranty-register')]);
                $add(['label' => 'لیست گارانتی‌ها', 'url' => url('/admin/serial-warranties')]);
                $add(['label' => 'شرکت‌های گارانتی', 'url' => url('/admin/warranty-companies')]);
            }

            return compact('label', 'primary', 'links');
        }

        if (in_array($path, ['contact', 'about', 'services', 'training', 'blog'], true)) {
            $label = match ($path) {
                'contact' => 'تماس',
                'about' => 'درباره ما',
                'services' => 'خدمات',
                'training' => 'آموزش',
                default => 'صفحه',
            };
            if ($path === 'about' && ($can('site.page_builder') || $can('site.homepage'))) {
                $primary = ['label' => 'ویرایش این صفحه', 'url' => url('/admin/about-page')];
                $add($can('site.page_builder') ? ['label' => 'صفحه‌ساز', 'url' => url('/admin/page-builder')] : null);
            } elseif ($path === 'services' && ($can('site.page_builder') || $can('site.homepage'))) {
                $primary = ['label' => 'ویرایش این صفحه', 'url' => url('/admin/services-page')];
                $add($can('site.page_builder') ? ['label' => 'صفحه‌ساز', 'url' => url('/admin/page-builder')] : null);
            } elseif ($path === 'contact' && ($can('site.page_builder') || $can('site.homepage'))) {
                $primary = ['label' => 'ویرایش این صفحه', 'url' => url('/admin/contact-page')];
                $add($can('site.page_builder') ? ['label' => 'صفحه‌ساز', 'url' => url('/admin/page-builder')] : null);
            } else {
                $page = static::findBuilderPage($path);
                if ($page && $can('site.page_builder')) {
                    $primary = ['label' => 'ویرایش این صفحه', 'url' => url('/admin/page-builder?page='.$page->id)];
                } elseif ($can('site.page_builder')) {
                    $primary = ['label' => 'صفحه‌ساز', 'url' => url('/admin/page-builder')];
                }
            }
            if ($can('site.mega_menu')) {
                $add(['label' => 'لینک منو', 'url' => url('/admin/mega-menu')]);
            }

            return compact('label', 'primary', 'links');
        }

        if (str_starts_with($path, 'account')) {
            $label = 'حساب مشتری';
            if ($can('site.shop_settings')) {
                $primary = ['label' => 'تنظیمات عضویت', 'url' => url('/admin/auth-settings')];
            }

            return compact('label', 'primary', 'links');
        }

        if ($path === 'cart' || $path === 'app/cart' || str_starts_with($path, 'checkout')) {
            $label = 'سبد / تسویه';
            if ($can('site.shop_settings')) {
                $primary = ['label' => 'تنظیمات فروشگاه', 'url' => url('/admin/settings')];
            }
            $add($can('orders') ? ['label' => 'سفارش‌ها', 'url' => url('/admin/orders')] : null);

            return compact('label', 'primary', 'links');
        }

        $slug = basename($path);
        $page = $slug !== '' ? static::findBuilderPage($slug) : null;
        if ($page) {
            $label = $page->title ?? $page->name ?? 'صفحه';
            if ($can('site.page_builder')) {
                $primary = ['label' => 'ویرایش این صفحه', 'url' => url('/admin/page-builder?page='.$page->id)];
            }

            return compact('label', 'primary', 'links');
        }

        if ($can('site.page_builder')) {
            $primary = ['label' => 'صفحه‌ساز', 'url' => url('/admin/page-builder')];
        } elseif ($can('site.mega_menu')) {
            $primary = ['label' => 'ویرایش منو', 'url' => url('/admin/mega-menu')];
        }

        return compact('label', 'primary', 'links');
    }

    protected static function findProduct(string $slug): ?object
    {
        $slug = rawurldecode($slug);
        try {
            if (class_exists(\Plugins\Catalog\src\Models\Product::class) && Schema::hasTable('products')) {
                return \Plugins\Catalog\src\Models\Product::query()->where('slug', $slug)->first();
            }
        } catch (\Throwable) {
        }

        return null;
    }

    protected static function findBuilderPage(string $slug): ?object
    {
        try {
            if (class_exists(\Plugins\ThemeBuilder\src\Models\BuilderPage::class) && Schema::hasTable('builder_pages')) {
                return \Plugins\ThemeBuilder\src\Models\BuilderPage::query()->where('slug', $slug)->first();
            }
        } catch (\Throwable) {
        }

        return null;
    }
}
