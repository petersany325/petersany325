<?php

use Illuminate\Support\Facades\Route;
use Plugins\WarrantyDesk\src\Http\Controllers\StorefrontController;

Route::get('/warranty-register', [StorefrontController::class, 'show'])->name('warranty.register');
Route::post('/warranty-register', [StorefrontController::class, 'store'])->name('warranty.register.store');
Route::get('/warranty-register/status', [StorefrontController::class, 'status'])->name('warranty.register.lookup');
Route::get('/warranty-register/{code}', [StorefrontController::class, 'status'])
    ->where('code', 'WR-[A-Za-z0-9]+')
    ->name('warranty.register.status');
