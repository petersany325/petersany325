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
