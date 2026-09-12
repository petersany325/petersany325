<?php

use Illuminate\Support\Facades\Route;
use Plugins\Accounting\src\Http\Controllers\Admin\HubController;

Route::prefix('accounting')->name('accounting.')->group(function () {
    Route::get('/', [HubController::class, 'hub'])->name('hub');
    Route::get('/docs', [HubController::class, 'docs'])->name('docs');
    Route::get('/docs/create', [HubController::class, 'createDoc'])->name('docs.create');
    Route::post('/docs', [HubController::class, 'storeDoc'])->name('docs.store');
    Route::get('/docs/{id}', [HubController::class, 'showDoc'])->name('doc')->whereNumber('id');
    Route::post('/docs/{id}/issue', [HubController::class, 'issueDoc'])->name('doc.issue')->whereNumber('id');
    Route::post('/docs/{id}/convert', [HubController::class, 'convertProforma'])->name('doc.convert')->whereNumber('id');

    Route::get('/warehouses', [HubController::class, 'warehouses'])->name('warehouses');
    Route::post('/warehouses', [HubController::class, 'storeWarehouse'])->name('warehouses.store');

    Route::get('/banks', [HubController::class, 'banks'])->name('banks');
    Route::post('/banks', [HubController::class, 'storeBank'])->name('banks.store');

    Route::get('/stock', [HubController::class, 'stock'])->name('stock');
    Route::post('/stock', [HubController::class, 'storeStockMove'])->name('stock.store');

    Route::get('/expenses', [HubController::class, 'expenses'])->name('expenses');
    Route::post('/expenses', [HubController::class, 'storeExpense'])->name('expenses.store');

    Route::get('/payroll', [HubController::class, 'payroll'])->name('payroll');
    Route::post('/payroll', [HubController::class, 'storePayroll'])->name('payroll.store');
    Route::get('/payroll/{id}', [HubController::class, 'showPayroll'])->name('payroll.show')->whereNumber('id');

    Route::get('/commissions', [HubController::class, 'commissions'])->name('commissions');
    Route::post('/commissions/{staffId}', [HubController::class, 'updateCommission'])->name('commissions.update')->whereNumber('staffId');

    Route::get('/reports', [HubController::class, 'reports'])->name('reports');
});
