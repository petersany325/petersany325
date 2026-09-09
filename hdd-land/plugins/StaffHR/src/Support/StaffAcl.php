<?php

namespace Plugins\StaffHR\src\Support;

/**
 * Staff ACL — dedicated file for Composer/PSR-4.
 * Guarded so it never clashes with the legacy inlined class in Plugin.php.
 */
if (class_exists(StaffAcl::class, false)) {
    return;
}

class StaffAcl
{
    /** @return array<string,string> */
    public static function permissionLabels(): array
    {
        return [
            'sales' => 'فروش و سفارش‌ها',
            'orders' => 'مشاهده/مدیریت سفارش',
            'serials' => 'سریال و گارانتی',
            'products.view' => 'مشاهده محصولات',
            'products.create' => 'افزودن محصول',
            'products.edit' => 'ویرایش محصول',
            'products.delete' => 'حذف محصول',
            'support' => 'پشتیبانی / تیکت',
            'accounting' => 'حسابداری',
            'reports' => 'گزارش فروش و کار',
            'media' => 'کتابخانه رسانه',
            'system_tools' => 'تعمیر و نگهداری',
            'site.mega_menu' => 'مدیریت مگامenu',
            'site.theme_builder' => 'استودیو قالب و بنرساز',
            'site.theme_templates' => 'نصب و به‌روزرسانی قالب',
            'site.page_builder' => 'صفحه‌ساز Elementor',
            'site.homepage' => 'بنر و تنظیمات صفحه اول',
            'site.footer' => 'تنظیمات فوتر',
            'site.webapp' => 'تنظیمات وب‌اپ / PWA',
            'site.shop_settings' => 'تنظیمات عمومی فروشگاه',
            'full_site' => 'دسترسی گسترده پنل کارمند',
        ];
    }

    /** @return array<string,array{label:string,permissions:list<string>}> */
    public static function rolePresets(): array
    {
        return [
            'sales_manager' => [
                'label' => 'مدیر فروش',
                'permissions' => [
                    'sales', 'orders', 'serials', 'products.view', 'products.create', 'products.edit',
                    'reports', 'media',
                ],
            ],
            'seller' => [
                'label' => 'فروشنده',
                'permissions' => ['sales', 'orders', 'serials', 'products.view', 'reports'],
            ],
            'support' => [
                'label' => 'پشتیبانی تیکت',
                'permissions' => ['support', 'orders'],
            ],
            'accountant' => [
                'label' => 'حسابداری',
                'permissions' => ['accounting', 'orders', 'reports'],
            ],
            'warehouse' => [
                'label' => 'انباردار',
                'permissions' => [
                    'products.view', 'products.create', 'products.edit', 'products.delete',
                    'serials', 'media',
                ],
            ],
            'technician' => [
                'label' => 'تعمیر و نگهداری',
                'permissions' => ['system_tools', 'reports'],
            ],
            'site_designer' => [
                'label' => 'مدیر ظاهر سایت',
                'permissions' => [
                    'site.mega_menu', 'site.theme_builder', 'site.theme_templates', 'site.page_builder',
                    'site.homepage', 'site.footer', 'site.webapp',
                ],
            ],
            'full_access' => [
                'label' => 'دسترسی کامل پنل',
                'permissions' => array_keys(self::permissionLabels()),
            ],
        ];
    }

    /** @return list<string> */
    public static function permissionsForRole(string $role): array
    {
        return self::rolePresets()[$role]['permissions'] ?? ['reports'];
    }

    /** @param  mixed  $raw @return list<string> */
    public static function normalizePermissions($raw): array
    {
        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            $raw = is_array($decoded) ? $decoded : [];
        }
        if (! is_array($raw)) {
            return [];
        }
        $allowed = array_keys(self::permissionLabels());
        $out = [];
        foreach ($raw as $p) {
            $p = (string) $p;
            if (in_array($p, $allowed, true)) {
                $out[] = $p;
            }
        }

        return array_values(array_unique($out));
    }

    public static function hasPermission(?object $staff, string $permission): bool
    {
        if (! $staff || empty($staff->is_active)) {
            return false;
        }
        $perms = self::normalizePermissions($staff->permissions ?? []);
        if (in_array('full_site', $perms, true) || in_array($permission, $perms, true)) {
            return true;
        }

        return false;
    }
}
