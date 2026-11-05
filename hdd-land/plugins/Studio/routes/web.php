<?php

use Illuminate\Support\Facades\Route;
use Plugins\Studio\src\Http\Controllers\StudioAuthController;
use Plugins\Studio\src\Http\Controllers\StudioController;
use Plugins\Studio\src\Http\Controllers\StudioPayController;
use Plugins\Studio\src\Http\Controllers\StudioStaffDeskController;
use Plugins\Studio\src\Http\Controllers\StudioSupportController;

Route::get('/auth/customer', [StudioAuthController::class, 'customer'])->name('studio.auth.customer');
Route::get('/auth/customer/register', fn () => redirect()->to(url('/auth/customer?tab=register')));
Route::post('/auth/customer/sms', [StudioAuthController::class, 'sendCustomer'])->name('studio.auth.customer.sms');
Route::post('/auth/customer/verify', [StudioAuthController::class, 'verifyCustomer'])->name('studio.auth.customer.verify');
Route::get('/auth/staff', [StudioAuthController::class, 'staff'])->name('studio.auth.staff');
Route::post('/auth/staff/sms', [StudioAuthController::class, 'sendStaff'])->name('studio.auth.staff.sms');
Route::post('/auth/staff/verify', [StudioAuthController::class, 'verifyStaff'])->name('studio.auth.staff.verify');
Route::post('/auth/restart', [StudioAuthController::class, 'restart'])->name('studio.auth.restart');
Route::post('/auth/logout', [StudioAuthController::class, 'logout'])->name('studio.auth.logout');

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
Route::get('/computer-accounting', [StudioController::class, 'computerAccounting'])->name('studio.computer');
Route::get('/computer-accounting/m/{slug}', [StudioController::class, 'computerAccountingItem'])->name('studio.computer.item');
Route::get('/computer-accounting/{slug}', [StudioController::class, 'computerAccountingType'])->name('studio.computer.type');
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

Route::get('/support', [StudioSupportController::class, 'show'])->name('studio.support');
Route::post('/support', [StudioSupportController::class, 'send'])->name('studio.support.send');

Route::get('/software/{slug}', [StudioController::class, 'softwareOffer'])->name('studio.software');

Route::get('/pay/start', [StudioPayController::class, 'start'])->name('studio.pay.start');
Route::get('/pay/zarinpal', [StudioPayController::class, 'start'])->name('studio.pay.zarinpal');
Route::get('/pay/melli', [StudioPayController::class, 'start'])->name('studio.pay.melli');
Route::post('/pay/record', [StudioPayController::class, 'record'])->name('studio.pay.record');

Route::get('/staff/desk', [StudioStaffDeskController::class, 'home'])->name('studio.staff.desk');
Route::get('/staff/desk/buy', [StudioStaffDeskController::class, 'buy'])->name('studio.staff.buy');
Route::get('/staff/desk/sell', [StudioStaffDeskController::class, 'sell'])->name('studio.staff.sell');
Route::get('/staff/desk/prices', [StudioStaffDeskController::class, 'prices'])->name('studio.staff.prices');
Route::get('/staff/desk/sales', [StudioStaffDeskController::class, 'sales'])->name('studio.staff.sales');
Route::get('/staff/desk/invoices', [StudioStaffDeskController::class, 'invoices'])->name('studio.staff.invoices');
Route::get('/staff/desk/reps', [StudioStaffDeskController::class, 'reps'])->name('studio.staff.reps');
Route::get('/staff/desk/tickets', [StudioStaffDeskController::class, 'tickets'])->name('studio.staff.tickets');
Route::post('/staff/desk/tickets', [StudioStaffDeskController::class, 'storeTicket'])->name('studio.staff.tickets.save');
Route::get('/staff/desk/sms', [StudioStaffDeskController::class, 'sms'])->name('studio.staff.sms');
Route::post('/staff/desk/sell', [StudioStaffDeskController::class, 'storeSale'])->name('studio.staff.sell.save');
