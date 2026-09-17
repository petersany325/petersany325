<?php

use Illuminate\Support\Facades\Route;
use Plugins\MegaMenu\src\Http\Controllers\WebsiteSalesController;

Route::get('/sites', [WebsiteSalesController::class, 'index'])->name('sites.index');
Route::get('/sites/repair-shop/staff-menu', [WebsiteSalesController::class, 'staffMenu'])
    ->name('sites.repair.staff-menu');
Route::get('/sites/repair-shop/{guide}', [WebsiteSalesController::class, 'repairGuide'])
    ->where('guide', 'referral|cost-approval|staff|trainee')
    ->name('sites.repair.guide');
Route::get('/sites/{slug}', [WebsiteSalesController::class, 'show'])
    ->where('slug', 'repair-shop|online-store|corporate|booking')
    ->name('sites.show');
