<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

/**
 * قانون: وقتی ادمین/کارمند وارد است، نوار تنظیمات بالای هر صفحه عمومی هست
 * و دکمه «ویرایش» همان صفحه را در پنل باز می‌کند.
 */
class AdminToolbar
{
    /** @return array<string, mixed>|null */
    public static function current(?Request $request = null): ?array
    {
        $request ??= request();
        $user = $request?->user();
        if (! $user) {
            return null;
        }

        $isAdmin = method_exists($user, 'isAdmin') && $user->isAdmin();
        $isStaff = method_exists($user, 'isStaff') && $user->isStaff();
        if (! $isAdmin && ! $isStaff) {
            return null;
        }

        $path = trim((string) $request->path(), '/');
        if ($path === 'admin' || str_starts_with($path, 'admin/') || $path === 'staff' || str_starts_with($path, 'staff/')) {
            return null;
        }

        $can = static fn (string $perm) => $isAdmin || (method_exists($user, 'hasStaffPermission') && $user->hasStaffPermission($perm));
        $ctx = static::context($request, $path, $can);

        $design = array_values(array_filter([
            $can('site.homepage') ? ['label' => 'صفحه اول استودیو', 'url' => url('/admin/studio-home')] : null,
            $can('site.homepage') ? ['label' => 'پیامک ورود / نیازپرداز', 'url' => url('/admin/studio-sms')] : null,
            $can('site.homepage') ? ['label' => 'بنر و هیرو فروشگاه', 'url' => url('/admin/homepage-settings')] : null,
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

        if (! $ctx['primary'] && $isAdmin) {
            $ctx['primary'] = ['label' => 'ویرایش', 'url' => url('/admin/studio-home')];
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
        $studio = class_exists(SiteHost::class) && SiteHost::isStudio($request);

        if ($path === '' || $path === '/') {
            $label = $studio ? 'خانه استودیو' : 'خانه فروشگاه';
            if ($studio) {
                $primary = static::builderEdit('/', $can) ?: ($can('site.homepage') ? ['label' => 'ویرایش', 'url' => url('/admin/studio-home')] : null);
                $add($can('site.homepage') ? ['label' => 'کلیدهای نمایش خانه', 'url' => url('/admin/studio-home')] : null);
                $add(['label' => 'بنر فروشگاه', 'url' => url('/admin/homepage-settings')]);
            } elseif ($can('site.homepage')) {
                $primary = ['label' => 'ویرایش', 'url' => url('/admin/homepage-settings')];
                $add(['label' => 'صفحه اول استودیو', 'url' => url('/admin/studio-home')]);
            }
            $add($can('site.mega_menu') ? ['label' => 'منوی بالا', 'url' => url('/admin/mega-menu')] : null);
            $add($can('site.footer') ? ['label' => 'فوتر', 'url' => url('/admin/footer-settings')] : null);

            return compact('label', 'primary', 'links');
        }

        if (str_starts_with($path, 'auth')) {
            $label = str_contains($path, 'staff') ? 'ورود کارمند' : 'ورود کاربر';
            if ($can('site.homepage')) {
                $primary = ['label' => 'ویرایش', 'url' => url('/admin/studio-sms')];
            }
            $add($can('site.shop_settings') ? ['label' => 'تنظیمات عضویت', 'url' => url('/admin/auth-settings')] : null);

            return compact('label', 'primary', 'links');
        }

        if ($path === 'login' || $path === 'register' || $path === 'forgot-password' || str_starts_with($path, 'login/')) {
            $label = 'ورود با رمز';
            if ($can('site.shop_settings')) {
                $primary = ['label' => 'ویرایش', 'url' => url('/admin/auth-settings')];
            } elseif ($can('site.homepage')) {
                $primary = ['label' => 'ویرایش', 'url' => url('/admin/studio-sms')];
            }

            return compact('label', 'primary', 'links');
        }

        if ($path === 'app' || $path === 'app/') {
            $label = 'خانه وب‌اپ';
            if ($can('site.webapp')) {
                $primary = ['label' => 'ویرایش', 'url' => url('/admin/web-app')];
            }
            $add($can('site.homepage') ? ['label' => 'صفحه اول استودیو', 'url' => url('/admin/studio-home')] : null);

            return compact('label', 'primary', 'links');
        }

        if ($path === 'app/shop' || $path === 'products') {
            $label = 'کاتالوگ';
            if ($can('products.view') || $can('products.edit')) {
                $primary = ['label' => 'ویرایش', 'url' => url('/admin/products')];
            }
            $add($can('products.edit') ? ['label' => 'دسته‌بندی‌ها', 'url' => url('/admin/categories')] : null);

            return compact('label', 'primary', 'links');
        }

        if (preg_match('#^(?:products|app/p|app/product)/([^/]+)$#', $path, $m)) {
            $product = static::findProduct($m[1]);
            $label = $product ? 'محصول: '.$product->name : 'محصول';
            if ($product && $can('products.edit')) {
                $primary = ['label' => 'ویرایش', 'url' => url('/admin/products/'.$product->id.'/edit')];
            } elseif ($can('products.view')) {
                $primary = ['label' => 'ویرایش', 'url' => url('/admin/products')];
            }
            $add($can('products.edit') ? ['label' => 'همه محصولات', 'url' => url('/admin/products')] : null);

            return compact('label', 'primary', 'links');
        }

        if (str_starts_with($path, 'design') || $path === 'ai' || $path === 'projects' || $path === 'about' || $path === 'contact' || $path === 'receipt-software') {
            $label = match (true) {
                str_starts_with($path, 'design') => 'طراحی سایت',
                $path === 'ai' => 'هوش مصنوعی',
                $path === 'projects' => 'نمونه کار',
                $path === 'about' => 'درباره ما',
                $path === 'contact' => 'تماس',
                default => 'صفحه استودیو',
            };
            $primary = static::builderEdit($path, $can);
            $add($can('site.mega_menu') ? ['label' => 'لینک منو', 'url' => url('/admin/mega-menu')] : null);

            return compact('label', 'primary', 'links');
        }

        if (str_starts_with($path, 'accounting') || str_starts_with($path, 'computer-accounting') || str_starts_with($path, 'repair-shop')) {
            $label = match (true) {
                str_starts_with($path, 'computer-accounting') => 'حسابداری کامپیوتر',
                str_starts_with($path, 'repair-shop') => 'تعمیرگاه',
                default => 'حسابداری وب',
            };
            $primary = static::builderEdit($path, $can);
            $add($can('site.mega_menu') ? ['label' => 'منوی حسابداری', 'url' => url('/admin/mega-menu')] : null);

            return compact('label', 'primary', 'links');
        }

        if ($path === 'sites' || str_starts_with($path, 'sites/')) {
            $label = 'فروش سایت';
            $primary = static::builderEdit($path, $can);
            $add($can('site.mega_menu') ? ['label' => 'مگامنو', 'url' => url('/admin/mega-menu')] : null);

            return compact('label', 'primary', 'links');
        }

        if (str_starts_with($path, 'account')) {
            $label = 'حساب مشتری';
            if ($can('site.shop_settings')) {
                $primary = ['label' => 'ویرایش', 'url' => url('/admin/auth-settings')];
            }

            return compact('label', 'primary', 'links');
        }

        if ($path === 'cart' || $path === 'app/cart' || str_starts_with($path, 'checkout')) {
            $label = 'سبد / تسویه';
            if ($can('site.shop_settings')) {
                $primary = ['label' => 'ویرایش', 'url' => url('/admin/settings')];
            }
            $add($can('orders') ? ['label' => 'سفارش‌ها', 'url' => url('/admin/orders')] : null);

            return compact('label', 'primary', 'links');
        }

        $built = static::builderEdit($path, $can);
        if ($built) {
            $found = static::findBuilderPageByPath($path);
            $label = (string) ($found['title'] ?? $found['menu_label'] ?? 'صفحه');
            $primary = $built;

            return compact('label', 'primary', 'links');
        }

        if ($studio && $can('site.homepage')) {
            $primary = ['label' => 'ویرایش', 'url' => url('/admin/studio-home')];
        } elseif ($can('site.page_builder')) {
            $primary = ['label' => 'ویرایش', 'url' => url('/admin/page-builder')];
        } elseif ($can('site.mega_menu')) {
            $primary = ['label' => 'ویرایش', 'url' => url('/admin/mega-menu')];
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

    /**
     * @param  callable(string):bool  $can
     * @return array{label:string,url:string}|null
     */
    protected static function builderEdit(string $path, callable $can): ?array
    {
        if (! $can('site.page_builder') && ! $can('site.homepage')) {
            return null;
        }
        $lookup = ($path === '' || $path === '/') ? '/' : '/'.trim($path, '/');
        $page = static::findBuilderPageByPath($path);
        if ($page !== null && ! empty($page['id'])) {
            return ['label' => 'ویرایش', 'url' => url('/admin/page-builder?page='.$page['id'])];
        }

        return ['label' => 'ویرایش', 'url' => url('/admin/page-builder?path='.rawurlencode($lookup))];
    }

    /** @return array<string, mixed>|null */
    protected static function findBuilderPageByPath(string $path): ?array
    {
        $lookup = ($path === '' || $path === '/') ? '/' : '/'.trim($path, '/');
        try {
            if (! class_exists(\Plugins\ThemeBuilder\src\PageBuilder::class)) {
                $file = base_path('plugins/ThemeBuilder/src/PageBuilder.php');
                if (is_file($file)) {
                    require_once $file;
                }
            }
            if (! class_exists(\Plugins\ThemeBuilder\src\PageBuilder::class)) {
                return null;
            }
            \Plugins\ThemeBuilder\src\PageBuilder::ensureReady();
            $page = \Plugins\ThemeBuilder\src\PageBuilder::findByPath($lookup);
            if ($page === null) {
                $page = \Plugins\ThemeBuilder\src\PageBuilder::findBySlug(basename($lookup));
            }

            return $page;
        } catch (\Throwable) {
            return null;
        }
    }

    protected static function findBuilderPage(string $slug): ?object
    {
        $page = static::findBuilderPageByPath($slug);

        return $page ? (object) $page : null;
    }
}
