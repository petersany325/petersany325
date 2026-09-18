<?php

use Illuminate\Support\Facades\Route;
use Plugins\ServicesDesk\src\Http\Controllers\Admin\ServicesController;

Route::get('services-page', [ServicesController::class, 'edit'])->name('services-page.edit');
Route::post('services-page', [ServicesController::class, 'save'])->name('services-page.save');
