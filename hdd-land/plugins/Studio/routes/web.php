<?php

use Illuminate\Support\Facades\Route;
use Plugins\Studio\src\Http\Controllers\StudioController;

Route::get('/studio-manifest.webmanifest', [StudioController::class, 'manifest'])->name('studio.manifest');
Route::get('/studio-sw.js', [StudioController::class, 'serviceWorker'])->name('studio.sw');
Route::post('/studio-webapp/dismiss', [StudioController::class, 'dismissBanner'])->name('studio.webapp.dismiss');
Route::get('/design', [StudioController::class, 'design'])->name('studio.design');
Route::get('/design/{slug}', [StudioController::class, 'designService'])->name('studio.design.service');
Route::get('/about', [StudioController::class, 'about'])->name('studio.about');
Route::get('/ai', [StudioController::class, 'ai'])->name('studio.ai');
Route::get('/accounting', [StudioController::class, 'accounting'])->name('studio.accounting');
Route::get('/accounting/m/{slug}', [StudioController::class, 'accountingItem'])->name('studio.accounting.item');
Route::get('/accounting/{slug}', [StudioController::class, 'accountingType'])->name('studio.accounting.type');
Route::get('/projects', [StudioController::class, 'projects'])->name('studio.projects');
Route::get('/receipt-software', [StudioController::class, 'receiptSoftware'])->name('studio.receipt');
Route::get('/contact', [StudioController::class, 'contact'])->name('studio.contact');

Route::get('/repair-shop', [StudioController::class, 'repairShop'])->name('studio.repair');
Route::get('/repair-shop/staff-menu', [StudioController::class, 'repairStaffMenu'])->name('studio.repair.staff');
Route::get('/repair-shop/customer-menu', [StudioController::class, 'repairCustomerMenu'])->name('studio.repair.customer');
Route::get('/repair-shop/m/{slug}', [StudioController::class, 'repairItem'])->name('studio.repair.item');

Route::get('/sites', [StudioController::class, 'sites'])->name('studio.sites');
Route::get('/sites/repair-shop', [StudioController::class, 'repairShop']);
Route::get('/sites/repair-shop/staff-menu', [StudioController::class, 'repairStaffMenu']);
Route::get('/sites/repair-shop/customer-menu', [StudioController::class, 'repairCustomerMenu']);
Route::get('/sites/repair-shop/m/{slug}', [StudioController::class, 'repairItem']);
Route::get('/sites/repair-shop/{slug}', [StudioController::class, 'repairItem']);
Route::get('/sites/{slug}', [StudioController::class, 'sitesSlug']);
