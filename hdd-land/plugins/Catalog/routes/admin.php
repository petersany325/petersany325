<?php

use Illuminate\Support\Facades\Route;
use Plugins\Catalog\src\Http\Controllers\Admin\CategoryController;
use Plugins\Catalog\src\Http\Controllers\Admin\MediaManagerController;
use Plugins\Catalog\src\Http\Controllers\Admin\ProductController;
use Plugins\Catalog\src\Http\Controllers\Admin\StorefrontDisplayController;

Route::get('products/display-settings', [StorefrontDisplayController::class, 'edit'])->name('products.display-settings');
Route::post('products/display-settings', [StorefrontDisplayController::class, 'update'])->name('products.display-settings.save');

Route::resource('products', ProductController::class)->except(['show']);
Route::get('products/{product}/serials', [\Plugins\Catalog\src\Http\Controllers\Admin\ProductSerialController::class, 'index'])->name('products.serials');
Route::post('products/{product}/serials', [\Plugins\Catalog\src\Http\Controllers\Admin\ProductSerialController::class, 'store'])->name('products.serials.store');
Route::post('products/{product}/serials/import', [\Plugins\Catalog\src\Http\Controllers\Admin\ProductSerialController::class, 'import'])->name('products.serials.import');
Route::delete('products/{product}/serials/{serialId}', [\Plugins\Catalog\src\Http\Controllers\Admin\ProductSerialController::class, 'destroy'])->name('products.serials.destroy');

Route::get('categories', [CategoryController::class, 'index'])->name('categories.index');
Route::get('categories/settings', [CategoryController::class, 'settings'])->name('categories.settings');
Route::post('categories/settings', [CategoryController::class, 'saveSettings'])->name('categories.settings.save');
Route::post('categories/reorder', [CategoryController::class, 'reorder'])->name('categories.reorder');
Route::post('categories', [CategoryController::class, 'store'])->name('categories.store');
Route::put('categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
Route::delete('categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');
Route::post('categories/{category}/duplicate', [CategoryController::class, 'duplicate'])->name('categories.duplicate');
Route::post('categories/{category}/move', [CategoryController::class, 'move'])->name('categories.move');

Route::get('media', [MediaManagerController::class, 'index'])->name('media.index');
Route::get('media/browse', [MediaManagerController::class, 'browse'])->name('media.browse');
Route::get('media/settings', [MediaManagerController::class, 'settings'])->name('media.settings');
Route::post('media/settings', [MediaManagerController::class, 'saveSettings'])->name('media.settings.save');
Route::post('media/mkdir', [MediaManagerController::class, 'mkdir'])->name('media.mkdir');
Route::post('media/upload', [MediaManagerController::class, 'upload'])->name('media.upload');
Route::post('media/delete', [MediaManagerController::class, 'delete'])->name('media.delete');
Route::post('media/rename', [MediaManagerController::class, 'rename'])->name('media.rename');
Route::post('media/move', [MediaManagerController::class, 'move'])->name('media.move');
Route::post('media/copy', [MediaManagerController::class, 'copy'])->name('media.copy');
