<?php

use Illuminate\Support\Facades\Route;
use Plugins\MegaMenu\src\Http\Controllers\WebsiteSalesController;

Route::get('/sites', [WebsiteSalesController::class, 'index'])->name('sites.index');
Route::get('/sites/repair-shop/staff-menu', [WebsiteSalesController::class, 'staffMenu'])
    ->name('sites.repair.staff-menu');
Route::get('/sites/repair-shop/customer-menu', [WebsiteSalesController::class, 'customerMenu'])
    ->name('sites.repair.customer-menu');
Route::get('/sites/repair-shop/m/{slug}', [WebsiteSalesController::class, 'menuItem'])
    ->where('slug', '[a-z0-9\-]+')
    ->name('sites.repair.menu-item');
Route::get('/sites/repair-shop/{guide}', [WebsiteSalesController::class, 'repairGuide'])
    ->where('guide', 'referral|cost-approval|staff|trainee')
    ->name('sites.repair.guide');
Route::get('/sites/{slug}', [WebsiteSalesController::class, 'show'])
    ->where('slug', 'repair-shop|online-store|corporate|booking')
    ->name('sites.show');
