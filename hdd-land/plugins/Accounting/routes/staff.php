<?php

use Illuminate\Support\Facades\Route;
use Plugins\Accounting\src\Http\Controllers\Staff\AccountingController;

Route::prefix('accounting')->name('accounting.')->group(function () {
    Route::get('/', [AccountingController::class, 'hub'])->name('hub');
    Route::get('/docs', [AccountingController::class, 'docs'])->name('docs');
    Route::get('/docs/{id}', [AccountingController::class, 'show'])->name('doc')->whereNumber('id');
    Route::get('/stock', [AccountingController::class, 'stock'])->name('stock');
    Route::get('/reports', [AccountingController::class, 'reports'])->name('reports');
});
