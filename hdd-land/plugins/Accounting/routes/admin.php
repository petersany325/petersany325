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
    Route::post('/docs/{id}/cancel', [HubController::class, 'cancelDoc'])->name('doc.cancel')->whereNumber('id');
    Route::delete('/docs/{id}', [HubController::class, 'deleteDoc'])->name('doc.delete')->whereNumber('id');
    Route::post('/docs/{id}/delete', [HubController::class, 'deleteDoc'])->name('doc.delete.post')->whereNumber('id');

    Route::get('/warehouses', [HubController::class, 'warehouses'])->name('warehouses');
    Route::post('/warehouses', [HubController::class, 'storeWarehouse'])->name('warehouses.store');
    Route::put('/warehouses/{id}', [HubController::class, 'updateWarehouse'])->name('warehouses.update')->whereNumber('id');
    Route::post('/warehouses/{id}/update', [HubController::class, 'updateWarehouse'])->name('warehouses.update.post')->whereNumber('id');
    Route::delete('/warehouses/{id}', [HubController::class, 'deleteWarehouse'])->name('warehouses.delete')->whereNumber('id');
    Route::post('/warehouses/{id}/delete', [HubController::class, 'deleteWarehouse'])->name('warehouses.delete.post')->whereNumber('id');

    Route::get('/banks', [HubController::class, 'banks'])->name('banks');
    Route::post('/banks', [HubController::class, 'storeBank'])->name('banks.store');
    Route::put('/banks/{id}', [HubController::class, 'updateBank'])->name('banks.update')->whereNumber('id');
    Route::post('/banks/{id}/update', [HubController::class, 'updateBank'])->name('banks.update.post')->whereNumber('id');
    Route::delete('/banks/{id}', [HubController::class, 'deleteBank'])->name('banks.delete')->whereNumber('id');
    Route::post('/banks/{id}/delete', [HubController::class, 'deleteBank'])->name('banks.delete.post')->whereNumber('id');

    Route::get('/stock', [HubController::class, 'stock'])->name('stock');
    Route::post('/stock', [HubController::class, 'storeStockMove'])->name('stock.store');

    Route::get('/expenses', [HubController::class, 'expenses'])->name('expenses');
    Route::post('/expenses', [HubController::class, 'storeExpense'])->name('expenses.store');

    Route::get('/payroll', [HubController::class, 'payroll'])->name('payroll');
    Route::post('/payroll', [HubController::class, 'storePayroll'])->name('payroll.store');
    Route::get('/payroll/{id}', [HubController::class, 'showPayroll'])->name('payroll.show')->whereNumber('id');
    Route::post('/payroll/{id}/delete', [HubController::class, 'deletePayroll'])->name('payroll.delete')->whereNumber('id');

    Route::get('/commissions', [HubController::class, 'commissions'])->name('commissions');
    Route::post('/commissions/{staffId}', [HubController::class, 'updateCommission'])->name('commissions.update')->whereNumber('staffId');

    Route::get('/settings', [HubController::class, 'settings'])->name('settings');
    Route::post('/settings/categories', [HubController::class, 'storeCategory'])->name('settings.categories.store');
    Route::post('/settings/categories/{id}/update', [HubController::class, 'updateCategory'])->name('settings.categories.update')->whereNumber('id');
    Route::post('/settings/categories/{id}/delete', [HubController::class, 'deleteCategory'])->name('settings.categories.delete')->whereNumber('id');
    Route::post('/settings/accounts', [HubController::class, 'storeAccount'])->name('settings.accounts.store');
    Route::post('/settings/accounts/{id}/update', [HubController::class, 'updateAccount'])->name('settings.accounts.update')->whereNumber('id');
    Route::post('/settings/accounts/{id}/delete', [HubController::class, 'deleteAccount'])->name('settings.accounts.delete')->whereNumber('id');

    Route::get('/reports', [HubController::class, 'reports'])->name('reports');
});
