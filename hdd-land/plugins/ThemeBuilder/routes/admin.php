<?php

use Illuminate\Support\Facades\Route;
use Plugins\ThemeBuilder\src\Http\Controllers\Admin\PageBuilderController;

Route::get('page-builder', [PageBuilderController::class, 'index'])->name('page-builder');
Route::post('page-builder', [PageBuilderController::class, 'save'])->name('page-builder.save');
Route::post('page-builder/upload', [PageBuilderController::class, 'upload'])->name('page-builder.upload');
