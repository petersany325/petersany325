<?php

use Illuminate\Support\Facades\Route;
use Plugins\MegaMenu\src\Http\Controllers\Admin\MegaMenuController;

Route::get('mega-menu', [MegaMenuController::class, 'index'])->name('mega-menu.index');
Route::post('mega-menu', [MegaMenuController::class, 'store'])->name('mega-menu.store');
Route::post('mega-menu/reorder', [MegaMenuController::class, 'reorder'])->name('mega-menu.reorder');
Route::post('mega-menu/upload', [MegaMenuController::class, 'upload'])->name('mega-menu.upload');
Route::post('mega-menu/settings', [MegaMenuController::class, 'saveSettings'])->name('mega-menu.settings');
Route::match(['put', 'post'], 'mega-menu/{menuItem}', [MegaMenuController::class, 'update'])->name('mega-menu.update');
Route::post('mega-menu/{menuItem}/toggle', [MegaMenuController::class, 'toggle'])->name('mega-menu.toggle');
Route::match(['delete', 'post'], 'mega-menu/{menuItem}/delete', [MegaMenuController::class, 'destroy'])->name('mega-menu.destroy');
Route::delete('mega-menu/{menuItem}', [MegaMenuController::class, 'destroy']);
