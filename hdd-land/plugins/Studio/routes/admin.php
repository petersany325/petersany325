<?php

use Illuminate\Support\Facades\Route;
use Plugins\Studio\src\Http\Controllers\Admin\StudioHomeController;
use Plugins\Studio\src\Http\Controllers\Admin\StudioSmsController;

Route::get('studio-home', [StudioHomeController::class, 'edit'])->name('studio-home');
Route::post('studio-home', [StudioHomeController::class, 'update'])->name('studio-home.save');
Route::get('studio-sms', [StudioSmsController::class, 'edit'])->name('studio-sms');
Route::post('studio-sms', [StudioSmsController::class, 'update'])->name('studio-sms.save');
