<?php

use Illuminate\Support\Facades\Route;
use Plugins\WarrantyDesk\src\Http\Controllers\Admin\DeskController;

Route::get('warranty-register', [DeskController::class, 'index'])->name('warranty-register.index');
Route::post('warranty-register/page', [DeskController::class, 'savePage'])->name('warranty-register.page');
Route::get('warranty-register/{id}', [DeskController::class, 'show'])->name('warranty-register.show')->whereNumber('id');
Route::post('warranty-register/{id}', [DeskController::class, 'update'])->name('warranty-register.update')->whereNumber('id');
