<?php

use Illuminate\Support\Facades\Route;
use Plugins\Studio\src\Http\Controllers\Admin\StudioHomeController;

Route::get('studio-home', [StudioHomeController::class, 'edit'])->name('studio-home');
Route::post('studio-home', [StudioHomeController::class, 'update'])->name('studio-home.save');
