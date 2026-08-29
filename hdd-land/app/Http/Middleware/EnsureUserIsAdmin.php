<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user) {
            return redirect()->guest(url('/login'));
        }

        if ($user->isAdmin()) {
            return $next($request);
        }

        // کارمند: فقط مسیرهای مجاز بر اساس ACL
        if (method_exists($user, 'isStaff') && $user->isStaff()) {
            $needed = $this->requiredPermission($request);
            $ok = $needed !== null && $user->hasStaffPermission($needed);
            if (! $ok && $needed === 'orders' && $user->hasStaffPermission('sales')) {
                $ok = true;
            }
            if (! $ok && $needed === 'serials' && $user->hasStaffPermission('sales')) {
                $ok = true;
            }
            if ($ok) {
                return $next($request);
            }
            if ($request->expectsJson()) {
                abort(403, 'دسترسی مجاز نیست.');
            }

            return redirect()->to(url('/staff'))
                ->with('error', 'به این بخش از پنل مدیریت دسترسی ندارید. از منوی پنل کارمند استفاده کنید.');
        }

        abort(403, 'دسترسی فقط برای مدیران.');
    }

    protected function requiredPermission(Request $request): ?string
    {
        $path = trim($request->path(), '/'); // admin/...
        $method = strtoupper($request->method());

        // تعمیر و نگهداری — با سوئیچ system_tools در تنظیمات کارمند
        if ($path === 'admin/system-tools' || str_starts_with($path, 'admin/system-tools/')) {
            return 'system_tools';
        }

        // مسیرهای حساس — فقط ادمین
        $adminOnly = [
            'admin/staff', 'admin/settings', 'admin/plugins',
            'admin/customers', 'admin/auth-settings', 'admin/wallet-settings',
            'admin/theme-builder', 'admin/theme-templates', 'admin/page-builder',
            'admin/mega-menu', 'admin/payment', 'admin/tickets/settings',
            'admin/developer-studio',
        ];
        foreach ($adminOnly as $prefix) {
            if ($path === $prefix || str_starts_with($path, $prefix.'/')) {
                return null;
            }
        }

        if (str_starts_with($path, 'admin/products')) {
            if ($method === 'DELETE' || str_contains($path, '/delete')) {
                return 'products.delete';
            }
            if ($method === 'POST' && (str_ends_with($path, '/products') || str_contains($path, '/create'))) {
                return 'products.create';
            }
            if (in_array($method, ['PUT', 'PATCH'], true) || str_contains($path, '/edit') || str_contains($path, '/serials')) {
                return str_contains($path, '/serials') ? 'serials' : 'products.edit';
            }
            if ($path === 'admin/products/create') {
                return 'products.create';
            }

            return 'products.view';
        }

        if (str_starts_with($path, 'admin/orders') || $path === 'admin/orders') {
            return 'orders';
        }
        if (str_starts_with($path, 'admin/serial') || str_starts_with($path, 'admin/warranty')) {
            return 'serials';
        }
        if (str_starts_with($path, 'admin/tickets')) {
            return 'support';
        }
        if (str_starts_with($path, 'admin/media') || str_starts_with($path, 'admin/categories')) {
            return str_starts_with($path, 'admin/media') ? 'media' : 'products.edit';
        }
        if (str_starts_with($path, 'admin/reports')) {
            return 'reports';
        }
        if (str_starts_with($path, 'admin/shipping') || str_starts_with($path, 'admin/invoice')) {
            return 'full_site';
        }
        // داشبورد ادمین برای کارمند ممنوع
        if ($path === 'admin' || $path === 'admin/') {
            return null;
        }

        return 'full_site';
    }
}
