<?php

use Illuminate\Support\Facades\Route;
use Plugins\AboutDesk\src\Http\Controllers\Admin\AboutController;

Route::get('about-page', [AboutController::class, 'edit'])->name('about-page.edit');
Route::post('about-page', [AboutController::class, 'save'])->name('about-page.save');
