<?php

use Illuminate\Support\Facades\Route;
use Plugins\ThemeBuilder\src\Http\Controllers\Admin\ThemeBuilderController;

/*
| Legacy Revolution / ThemeBuilder admin.
| All endpoints redirect safely to modern Hero Studio to stop HTTP 500s.
*/
Route::get('theme-builder', [ThemeBuilderController::class, 'index'])->name('theme-builder');
Route::post('theme-builder', [ThemeBuilderController::class, 'save'])->name('theme-builder.save');
Route::match(['get', 'post', 'put', 'patch', 'delete'], 'theme-builder/{any}', [ThemeBuilderController::class, 'fallback'])
    ->where('any', '.*')
    ->name('theme-builder.catch');
