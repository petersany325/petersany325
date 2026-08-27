<?php

use Illuminate\Support\Facades\Route;
use Plugins\AuthCustomers\src\Http\Controllers\PasswordResetController;
use Plugins\AuthCustomers\src\Http\Controllers\SitePageController;
use Plugins\AuthCustomers\src\Http\Controllers\AccountController;
use Plugins\AuthCustomers\src\Http\Controllers\AuthController;

Route::get('/geo/provinces', [\Plugins\AuthCustomers\src\Http\Controllers\GeoController::class, 'provinces'])->name('geo.provinces');
Route::get('/geo/cities', [\Plugins\AuthCustomers\src\Http\Controllers\GeoController::class, 'cities'])->name('geo.cities');

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::get('/login/2fa', [AuthController::class, 'showTwoFactor'])->name('login.2fa');

Route::get('/terms', [SitePageController::class, 'terms'])->name('terms');
Route::get('/privacy', [SitePageController::class, 'privacy'])->name('privacy');

Route::get('/forgot-password', [PasswordResetController::class, 'showForgot'])->name('password.request');
Route::post('/forgot-password', [PasswordResetController::class, 'sendCode'])->name('password.email');
Route::get('/reset-password', [PasswordResetController::class, 'showReset'])->name('password.reset.form');
Route::post('/reset-password', [PasswordResetController::class, 'reset'])->name('password.reset');

Route::middleware('guest')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/login/2fa', [AuthController::class, 'verifyTwoFactor'])->name('login.2fa.verify');
    Route::post('/register', [AuthController::class, 'register']);
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware('auth')->prefix('account')->name('account.')->group(function () {
    Route::get('/', [AccountController::class, 'index'])->name('index');
    Route::get('/orders', [AccountController::class, 'orders'])->name('orders');
    Route::get('/orders/{order}', [AccountController::class, 'orderShow'])->name('orders.show');
    Route::get('/serials', [AccountController::class, 'serials'])->name('serials');
    Route::get('/invoices', [AccountController::class, 'invoices'])->name('invoices');
    Route::get('/invoices/{order}', [AccountController::class, 'invoiceShow'])->name('invoices.show');
    Route::get('/wallet', [\Plugins\AuthCustomers\src\Http\Controllers\WalletController::class, 'index'])->name('wallet');
    Route::post('/wallet/charge', [\Plugins\AuthCustomers\src\Http\Controllers\WalletController::class, 'charge'])->name('wallet.charge');
    Route::get('/wallet/callback', [\Plugins\AuthCustomers\src\Http\Controllers\WalletController::class, 'callback'])->name('wallet.callback');
    Route::post('/wallet/sandbox', [\Plugins\AuthCustomers\src\Http\Controllers\WalletController::class, 'sandboxPay'])->name('wallet.sandbox');
    Route::get('/profile', [AccountController::class, 'profile'])->name('profile');
    Route::post('/profile', [AccountController::class, 'updateProfile'])->name('profile.update');
    Route::get('/password', [AccountController::class, 'passwordForm'])->name('password');
    Route::post('/password', [AccountController::class, 'updatePassword'])->name('password.update');
    Route::get('/security', [AccountController::class, 'security'])->name('security');
    Route::post('/security/2fa', [AccountController::class, 'enableTwoFactor'])->name('security.2fa');
    Route::get('/verify-phone', [AccountController::class, 'showVerifyPhone'])->name('verify.phone');
    Route::post('/verify-phone/send', [AccountController::class, 'sendPhoneOtp'])->name('verify.phone.send');
    Route::post('/verify-phone/confirm', [AccountController::class, 'confirmPhoneOtp'])->name('verify.phone.confirm');
    Route::get('/preorders', [AccountController::class, 'preorders'])->name('preorders');
    Route::post('/preorders', [AccountController::class, 'storePreorder'])->name('preorders.store');
    Route::get('/shop', [AccountController::class, 'shop'])->name('shop');

    Route::get('/tickets', [\Plugins\AuthCustomers\src\Http\Controllers\TicketController::class, 'index'])->name('tickets');
    Route::get('/tickets/create', [\Plugins\AuthCustomers\src\Http\Controllers\TicketController::class, 'create'])->name('tickets.create');
    Route::post('/tickets', [\Plugins\AuthCustomers\src\Http\Controllers\TicketController::class, 'store'])->name('tickets.store');
    Route::get('/tickets/{ticket}', [\Plugins\AuthCustomers\src\Http\Controllers\TicketController::class, 'show'])->name('tickets.show')->whereNumber('ticket');
    Route::post('/tickets/{ticket}/reply', [\Plugins\AuthCustomers\src\Http\Controllers\TicketController::class, 'reply'])->name('tickets.reply')->whereNumber('ticket');
    Route::post('/tickets/{ticket}/close', [\Plugins\AuthCustomers\src\Http\Controllers\TicketController::class, 'close'])->name('tickets.close')->whereNumber('ticket');
});
