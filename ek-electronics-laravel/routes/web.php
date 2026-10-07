<?php

use App\Http\Controllers\Install\InstallController;
use App\Http\Controllers\StoreController;
use App\Http\Middleware\EnsureNotInstalled;
use Illuminate\Support\Facades\Route;

Route::middleware(EnsureNotInstalled::class)->group(function () {
    Route::get('/install', [InstallController::class, 'show'])->name('install.show');
    Route::post('/install', [InstallController::class, 'store'])->name('install.store');
});

Route::get('/', [StoreController::class, 'home'])->name('home');
Route::get('/shop', [StoreController::class, 'shop'])->name('shop');
Route::get('/services', [StoreController::class, 'services'])->name('services');
Route::get('/about', [StoreController::class, 'about'])->name('about');
Route::get('/contact', [StoreController::class, 'contact'])->name('contact');
Route::post('/contact', [StoreController::class, 'contactWhatsApp'])->name('contact.whatsapp');
Route::get('/page/{slug}', [StoreController::class, 'page'])->name('page');
Route::get('/cart', [StoreController::class, 'cart'])->name('cart');
Route::post('/cart/add', [StoreController::class, 'addToCart'])->name('cart.add');
Route::post('/cart/update', [StoreController::class, 'updateCart'])->name('cart.update');
Route::get('/checkout', [StoreController::class, 'checkout'])->name('checkout');
Route::post('/checkout', [StoreController::class, 'placeOrder'])->name('checkout.place');
