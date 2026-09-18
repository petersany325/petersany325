<?php

use Illuminate\Support\Facades\Route;
use Plugins\Accounting\src\Http\Controllers\Account\InstallmentController;
use Plugins\Accounting\src\Http\Controllers\Account\InvoiceController;

Route::middleware('auth')->prefix('account')->name('account.')->group(function () {
    Route::get('/invoices', [InvoiceController::class, 'index'])->name('invoices');
    Route::get('/invoices/{id}', [InvoiceController::class, 'show'])->name('invoices.show')->whereNumber('id');

    Route::get('/installments', [InstallmentController::class, 'index'])->name('installments');
    Route::get('/installments/create', [InstallmentController::class, 'create'])->name('installments.create');
    Route::post('/installments', [InstallmentController::class, 'store'])->name('installments.store');
    Route::get('/installments/{id}', [InstallmentController::class, 'show'])->name('installments.show')->whereNumber('id');
});
