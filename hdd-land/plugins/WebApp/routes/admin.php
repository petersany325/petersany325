<?php

use Illuminate\Support\Facades\Route;
use Plugins\WebApp\src\Http\Controllers\Admin\HeroStudioController;
use Plugins\WebApp\src\Http\Controllers\Admin\HomepageSettingsController;
use Plugins\WebApp\src\Http\Controllers\Admin\HomepageTilesController;

Route::get('hero-studio', [HeroStudioController::class, 'edit'])->name('hero-studio');
Route::post('hero-studio', [HeroStudioController::class, 'update'])->name('hero-studio.save');

Route::get('home-options', [HomepageTilesController::class, 'edit'])->name('home-options');
Route::post('home-options', [HomepageTilesController::class, 'update'])->name('home-options.save');
Route::post('home-options/apply', [HomepageTilesController::class, 'apply'])->name('home-options.apply');

Route::get('homepage-settings', [HomepageSettingsController::class, 'edit'])->name('homepage-settings');
Route::post('homepage-settings', [HomepageSettingsController::class, 'update'])->name('homepage-settings.save');
Route::get('online-home', [HomepageSettingsController::class, 'edit'])->name('online-home');
Route::post('online-home', [HomepageSettingsController::class, 'update'])->name('online-home.save');
Route::get('banner-settings', [HeroStudioController::class, 'edit'])->name('banner-settings');
Route::post('banner-settings', [HeroStudioController::class, 'update'])->name('banner-settings.save');
