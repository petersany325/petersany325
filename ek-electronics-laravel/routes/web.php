<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Install\InstallController;
use App\Http\Controllers\StaffPortalController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\WhatsappWebhookController;
use App\Http\Middleware\EnsureNotInstalled;
use Illuminate\Support\Facades\Route;

Route::middleware(EnsureNotInstalled::class)->group(function () {
    Route::get('/install', [InstallController::class, 'show'])->name('install.show');
    Route::post('/install', [InstallController::class, 'store'])->name('install.store');
});

Route::get('/webhooks/whatsapp', [WhatsappWebhookController::class, 'verify'])->name('webhooks.whatsapp.verify');
Route::post('/webhooks/whatsapp', [WhatsappWebhookController::class, 'receive'])->name('webhooks.whatsapp.receive');

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

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.submit');
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware(['auth', 'role:customer,staff,admin'])->prefix('account')->name('account.')->group(function () {
    Route::get('/', [AccountController::class, 'dashboard'])->name('dashboard');
    Route::get('/orders', [AccountController::class, 'orders'])->name('orders');
    Route::get('/tickets', [AccountController::class, 'tickets'])->name('tickets');
    Route::get('/tickets/create', [AccountController::class, 'createTicket'])->name('tickets.create');
    Route::post('/tickets', [AccountController::class, 'storeTicket'])->name('tickets.store');
    Route::get('/tickets/{ticket}', [AccountController::class, 'showTicket'])->name('tickets.show');
    Route::post('/tickets/{ticket}/reply', [AccountController::class, 'replyTicket'])->name('tickets.reply');
});

Route::middleware(['auth', 'role:staff,admin'])->prefix('staff')->name('staff.')->group(function () {
    Route::get('/', [StaffPortalController::class, 'dashboard'])->name('dashboard');
    Route::get('/orders', [StaffPortalController::class, 'orders'])->name('orders');
    Route::get('/tickets', [StaffPortalController::class, 'tickets'])->name('tickets');
    Route::get('/tickets/{ticket}', [StaffPortalController::class, 'showTicket'])->name('tickets.show');
    Route::post('/tickets/{ticket}/reply', [StaffPortalController::class, 'replyTicket'])->name('tickets.reply');
    Route::get('/accounting', [StaffPortalController::class, 'accounting'])->name('accounting');
});
