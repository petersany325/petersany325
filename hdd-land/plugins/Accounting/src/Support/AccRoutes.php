<?php

namespace Plugins\Accounting\src\Support;

use Illuminate\Support\Facades\Route;
use Plugins\Accounting\src\Http\Controllers\Account\InstallmentController;
use Plugins\Accounting\src\Http\Controllers\Account\InvoiceController;

/**
 * Customer invoice/installment URLs.
 * Registered from Accounting and again from WebApp so a missing plugin-boot
 * never leaves «اقساط» as a dead 404 menu link.
 */
class AccRoutes
{
    protected static bool $customer = false;

    protected static bool $admin = false;

    public static function registerCustomer(): void
    {
        if (self::$customer) {
            return;
        }
        self::$customer = true;

        $plugin = dirname(__DIR__, 2).DIRECTORY_SEPARATOR.'Plugin.php';
        $base = dirname(__DIR__, 2).DIRECTORY_SEPARATOR.'src';
        foreach ([
            $plugin,
            $base.'/Support/AccMath.php',
            $base.'/Support/AccEngine.php',
            $base.'/Support/AccCommerce.php',
            $base.'/Http/Controllers/Account/InvoiceController.php',
            $base.'/Http/Controllers/Account/InstallmentController.php',
        ] as $file) {
            if (is_file($file)) {
                try {
                    require_once $file;
                } catch (\Throwable) {
                }
            }
        }

        if (! class_exists(InstallmentController::class)) {
            return;
        }

        self::registerViews();

        Route::middleware(['web', 'auth'])->group(function () {
            Route::prefix('account')->name('account.')->group(function () {
                if (! Route::has('account.invoices') && class_exists(InvoiceController::class)) {
                    Route::get('/invoices', [InvoiceController::class, 'index'])->name('invoices');
                    Route::get('/invoices/{id}', [InvoiceController::class, 'show'])->name('invoices.show')->whereNumber('id');
                }
                if (! Route::has('account.installments')) {
                    Route::get('/installments', [InstallmentController::class, 'index'])->name('installments');
                    Route::get('/installments/create', [InstallmentController::class, 'create'])->name('installments.create');
                    Route::post('/installments', [InstallmentController::class, 'store'])->name('installments.store');
                    Route::get('/installments/{id}', [InstallmentController::class, 'show'])->name('installments.show')->whereNumber('id');
                }
            });

            $index = [InstallmentController::class, 'index'];
            $create = [InstallmentController::class, 'create'];
            $store = [InstallmentController::class, 'store'];
            $show = [InstallmentController::class, 'show'];

            foreach ([
                'account/aqsat',
                'account/ghest',
                'account/ghesti',
                'account/installment',
                'app/installments',
                'app/account/installments',
            ] as $path) {
                Route::get($path, $index);
            }
            foreach ([
                'account/aqsat/create',
                'account/ghest/create',
                'account/installment/create',
                'app/installments/create',
            ] as $path) {
                Route::get($path, $create);
            }
            foreach ([
                'account/aqsat',
                'account/ghest',
                'account/installment',
                'app/installments',
            ] as $path) {
                Route::post($path, $store);
            }
            foreach ([
                'account/aqsat/{id}',
                'account/ghest/{id}',
                'account/installment/{id}',
                'app/installments/{id}',
            ] as $path) {
                Route::get($path, $show)->whereNumber('id');
            }
        });
    }

    /**
     * Add missing admin accounting URLs after plugin routes boot.
     * Never re-registers a named route that already exists.
     */
    public static function registerAdminFallbacks(): void
    {
        if (self::$admin) {
            return;
        }
        self::$admin = true;
        self::registerViews();
        $base = dirname(__DIR__, 2).DIRECTORY_SEPARATOR.'src';
        foreach ([
            $base.'/Http/Controllers/Admin/HubController.php',
            $base.'/Http/Controllers/Admin/StaffController.php',
            $base.'/Http/Controllers/Admin/CheckController.php',
            $base.'/Http/Controllers/Admin/ReportController.php',
            $base.'/Http/Controllers/Admin/ChartController.php',
        ] as $file) {
            if (is_file($file)) {
                try {
                    require_once $file;
                } catch (\Throwable) {
                }
            }
        }

        $mw = ['web', 'auth'];
        try {
            Route::middleware($mw)->group(function () {
                $map = [
                    'admin.accounting.staff' => ['get', 'admin/accounting/staff', \Plugins\Accounting\src\Http\Controllers\Admin\StaffController::class, 'index'],
                    'admin.accounting.staff.store' => ['post', 'admin/accounting/staff', \Plugins\Accounting\src\Http\Controllers\Admin\StaffController::class, 'store'],
                    'admin.accounting.goods' => ['get', 'admin/accounting/goods', \Plugins\Accounting\src\Http\Controllers\Admin\HubController::class, 'goods'],
                    'admin.accounting.goods.store' => ['post', 'admin/accounting/goods', \Plugins\Accounting\src\Http\Controllers\Admin\HubController::class, 'storeGood'],
                    'admin.accounting.checkbooks' => ['get', 'admin/accounting/checkbooks', \Plugins\Accounting\src\Http\Controllers\Admin\CheckController::class, 'books'],
                    'admin.accounting.checkbooks.store' => ['post', 'admin/accounting/checkbooks', \Plugins\Accounting\src\Http\Controllers\Admin\CheckController::class, 'storeBook'],
                    'admin.accounting.checks.received' => ['get', 'admin/accounting/checks/received', \Plugins\Accounting\src\Http\Controllers\Admin\CheckController::class, 'received'],
                    'admin.accounting.checks.spent' => ['get', 'admin/accounting/checks/spent', \Plugins\Accounting\src\Http\Controllers\Admin\CheckController::class, 'spent'],
                    'admin.accounting.checks.alerts' => ['get', 'admin/accounting/checks/alerts', \Plugins\Accounting\src\Http\Controllers\Admin\CheckController::class, 'alerts'],
                    'admin.accounting.checks.alerts.save' => ['post', 'admin/accounting/checks/alerts', \Plugins\Accounting\src\Http\Controllers\Admin\CheckController::class, 'saveAlertSetting'],
                    'admin.accounting.chart' => ['get', 'admin/accounting/chart', \Plugins\Accounting\src\Http\Controllers\Admin\ChartController::class, 'index'],
                    'admin.accounting.reports.shop-stock' => ['get', 'admin/accounting/reports/shop-stock', \Plugins\Accounting\src\Http\Controllers\Admin\ReportController::class, 'shopStock'],
                    'admin.accounting.reports.trial' => ['get', 'admin/accounting/reports/trial', \Plugins\Accounting\src\Http\Controllers\Admin\ChartController::class, 'trial'],
                    'admin.accounting.reports.income' => ['get', 'admin/accounting/reports/income', \Plugins\Accounting\src\Http\Controllers\Admin\ChartController::class, 'income'],
                    'admin.accounting.reports.balance' => ['get', 'admin/accounting/reports/balance', \Plugins\Accounting\src\Http\Controllers\Admin\ChartController::class, 'balanceSheet'],
                ];
                foreach ($map as $name => [$method, $path, $cls, $action]) {
                    if (Route::has($name) || ! class_exists($cls)) {
                        continue;
                    }
                    try {
                        Route::{$method}($path, [$cls, $action])->name($name);
                    } catch (\Throwable) {
                    }
                }
                try {
                    if (! Route::has('admin.accounting.staff.update') && class_exists(\Plugins\Accounting\src\Http\Controllers\Admin\StaffController::class)) {
                        Route::post('admin/accounting/staff/{id}/update', [\Plugins\Accounting\src\Http\Controllers\Admin\StaffController::class, 'update'])->name('admin.accounting.staff.update')->whereNumber('id');
                    }
                    if (! Route::has('admin.accounting.staff.delete') && class_exists(\Plugins\Accounting\src\Http\Controllers\Admin\StaffController::class)) {
                        Route::post('admin/accounting/staff/{id}/delete', [\Plugins\Accounting\src\Http\Controllers\Admin\StaffController::class, 'destroy'])->name('admin.accounting.staff.delete')->whereNumber('id');
                    }
                    if (! Route::has('admin.accounting.checkbooks.update') && class_exists(\Plugins\Accounting\src\Http\Controllers\Admin\CheckController::class)) {
                        Route::post('admin/accounting/checkbooks/{id}/update', [\Plugins\Accounting\src\Http\Controllers\Admin\CheckController::class, 'updateBook'])->name('admin.accounting.checkbooks.update')->whereNumber('id');
                    }
                } catch (\Throwable) {
                }
            });
        } catch (\Throwable) {
        }
    }

    public static function registerViews(): void
    {
        $views = dirname(__DIR__, 2).DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'views';
        if (! is_dir($views)) {
            return;
        }
        try {
            \Illuminate\Support\Facades\View::addNamespace('accounting', $views);
            \Illuminate\Support\Facades\View::addNamespace('Accounting', $views);
        } catch (\Throwable) {
        }
    }
}
