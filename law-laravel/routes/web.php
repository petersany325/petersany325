<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\Install\InstallController;
use App\Http\Controllers\PwaController;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\WebAppController;
use App\Http\Middleware\EnsureNotInstalled;
use App\Http\Middleware\RedirectMobileToWebApp;
use Illuminate\Support\Facades\Route;

Route::middleware(EnsureNotInstalled::class)->group(function () {
    Route::get('/install', [InstallController::class, 'show'])->name('install.show');
    Route::post('/install', [InstallController::class, 'store'])->name('install.store');
});

Route::get('/manifest.webmanifest', [PwaController::class, 'manifest'])->name('pwa.manifest');
Route::get('/sw.js', [PwaController::class, 'serviceWorker'])->name('pwa.sw');

Route::middleware(RedirectMobileToWebApp::class)->group(function () {
    Route::get('/', [HomeController::class, 'index'])->name('home');
});

Route::get('/app', [WebAppController::class, 'index'])->name('app.home');

Route::post('/contact', [HomeController::class, 'contact'])->name('contact.store');
Route::post('/appointments', [HomeController::class, 'appointment'])->name('appointments.store');

Route::get('/blog', [SiteController::class, 'blog'])->name('blog.index');
Route::get('/blog/{post:slug}', [SiteController::class, 'blogShow'])->name('blog.show');
Route::get('/team', [SiteController::class, 'team'])->name('team');
Route::get('/faq', [SiteController::class, 'faq'])->name('faq');
Route::get('/p/{page:slug}', [SiteController::class, 'page'])->name('pages.show');
