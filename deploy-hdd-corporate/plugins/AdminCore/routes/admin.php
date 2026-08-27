<?php

use Illuminate\Support\Facades\Route;
use Plugins\AdminCore\src\Http\Controllers\DashboardController;
use Plugins\AdminCore\src\Http\Controllers\Phase2Controller;
use Plugins\AdminCore\src\Http\Controllers\SystemToolsController;
use Plugins\AdminCore\src\Http\Controllers\MarketingHubController;
use Plugins\AdminCore\src\Http\Controllers\FooterSettingsController;
use Plugins\AdminCore\src\Http\Controllers\CorporateHomeSettingsController;

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/orders', [DashboardController::class, 'orders'])->name('orders.index');
Route::get('/orders/{order}', [DashboardController::class, 'orderShow'])->name('orders.show');
Route::put('/orders/{order}', [DashboardController::class, 'orderUpdate'])->name('orders.update');
Route::get('/settings', [DashboardController::class, 'settings'])->name('settings');
Route::post('/settings', [DashboardController::class, 'saveSettings'])->name('settings.save');
Route::get('/plugins', [DashboardController::class, 'plugins'])->name('plugins.index');
Route::get('/developer-studio', [DashboardController::class, 'developerStudio'])->name('developer-studio.index');
Route::post('/plugins/{pluginId}/toggle', [DashboardController::class, 'togglePlugin'])->name('plugins.toggle');
Route::post('/plugins/rescan', [DashboardController::class, 'rescanPlugins'])->name('plugins.rescan');
Route::post('/plugins/repair', [DashboardController::class, 'repairPlugins'])->name('plugins.repair');
Route::post('/plugins/{pluginId}/update', [DashboardController::class, 'updatePlugin'])->name('plugins.update');

Route::get('/system-tools', [SystemToolsController::class, 'index'])->name('system-tools');
Route::post('/system-tools', [SystemToolsController::class, 'run'])->name('system-tools.run');
Route::get('/marketing-hub', [MarketingHubController::class, 'index'])->name('marketing-hub');
Route::post('/marketing-hub', [MarketingHubController::class, 'save'])->name('marketing-hub.save');
Route::post('/marketing-hub/sync', [MarketingHubController::class, 'sync'])->name('marketing-hub.sync');
Route::get('/footer-settings', [FooterSettingsController::class, 'index'])->name('footer-settings');
Route::post('/footer-settings', [FooterSettingsController::class, 'save'])->name('footer-settings.save');
Route::get('/corporate-home', [CorporateHomeSettingsController::class, 'index'])->name('corporate-home');
Route::post('/corporate-home', [CorporateHomeSettingsController::class, 'save'])->name('corporate-home.save');

/* فاز ۲ — منوهای جدید */
Route::get('shipping-post', [Phase2Controller::class, 'shippingPost'])->name('shipping-post');
Route::post('shipping-post', [Phase2Controller::class, 'saveShippingPost'])->name('shipping-post.save');
Route::get('shipping-tipax', [Phase2Controller::class, 'shippingTipax'])->name('shipping-tipax');
Route::post('shipping-tipax', [Phase2Controller::class, 'saveShippingTipax'])->name('shipping-tipax.save');
Route::get('smart-chat', [Phase2Controller::class, 'smartChat'])->name('smart-chat');
Route::post('smart-chat', [Phase2Controller::class, 'saveSmartChat'])->name('smart-chat.save');
Route::get('web-app', [Phase2Controller::class, 'webApp'])->name('web-app');
Route::post('web-app', [Phase2Controller::class, 'saveWebApp'])->name('web-app.save');
Route::get('serial-sales', [Phase2Controller::class, 'serialSales'])->name('serial-sales');
Route::get('serial-warranties', [Phase2Controller::class, 'serialWarranties'])->name('serial-warranties');
Route::post('serial-sales/settings', [Phase2Controller::class, 'saveSerialSettings'])->name('serial-sales.settings');
Route::post('serial-sales/import', [Phase2Controller::class, 'importSerial'])->name('serial-sales.import');
Route::post('serial-sales', [Phase2Controller::class, 'storeSerial'])->name('serial-sales.store');
Route::post('serial-sales/{id}/sell', [Phase2Controller::class, 'sellSerial'])->name('serial-sales.sell');
Route::post('serial-sales/{id}/delete', [Phase2Controller::class, 'deleteSerial'])->name('serial-sales.delete');

Route::get('warranty-companies', [\Plugins\AdminCore\src\Http\Controllers\WarrantyCompanyController::class, 'index'])->name('warranty-companies');
Route::post('warranty-companies', [\Plugins\AdminCore\src\Http\Controllers\WarrantyCompanyController::class, 'store'])->name('warranty-companies.store');
Route::get('warranty-companies/{id}/edit', [\Plugins\AdminCore\src\Http\Controllers\WarrantyCompanyController::class, 'edit'])->name('warranty-companies.edit');
Route::put('warranty-companies/{id}', [\Plugins\AdminCore\src\Http\Controllers\WarrantyCompanyController::class, 'update'])->name('warranty-companies.update');
Route::delete('warranty-companies/{id}', [\Plugins\AdminCore\src\Http\Controllers\WarrantyCompanyController::class, 'destroy'])->name('warranty-companies.destroy');

Route::get('shipping-carriers', [\Plugins\AdminCore\src\Http\Controllers\ShippingCarrierController::class, 'index'])->name('shipping-carriers');
Route::post('shipping-carriers', [\Plugins\AdminCore\src\Http\Controllers\ShippingCarrierController::class, 'store'])->name('shipping-carriers.store');
Route::get('shipping-carriers/{id}/edit', [\Plugins\AdminCore\src\Http\Controllers\ShippingCarrierController::class, 'edit'])->name('shipping-carriers.edit');
Route::put('shipping-carriers/{id}', [\Plugins\AdminCore\src\Http\Controllers\ShippingCarrierController::class, 'update'])->name('shipping-carriers.update');
Route::delete('shipping-carriers/{id}', [\Plugins\AdminCore\src\Http\Controllers\ShippingCarrierController::class, 'destroy'])->name('shipping-carriers.destroy');

Route::get('invoice-design', [\Plugins\AdminCore\src\Http\Controllers\InvoiceDesignController::class, 'index'])->name('invoice-design');
Route::post('invoice-design', [\Plugins\AdminCore\src\Http\Controllers\InvoiceDesignController::class, 'save'])->name('invoice-design.save');
Route::get('invoice-design/preview', [\Plugins\AdminCore\src\Http\Controllers\InvoiceDesignController::class, 'preview'])->name('invoice-design.preview');
Route::get('orders/{order}/invoice', [\Plugins\AdminCore\src\Http\Controllers\InvoiceDesignController::class, 'orderDocument'])->name('orders.invoice');

$staffAdmin = \Plugins\AdminCore\src\Http\Controllers\StaffAdminController::class;
Route::get('staff', [$staffAdmin, 'index'])->name('staff');
Route::get('staff/create', [$staffAdmin, 'create'])->name('staff.create');
Route::get('staff/reports', [$staffAdmin, 'reports'])->name('staff.reports');
Route::get('staff/activity', [$staffAdmin, 'activity'])->name('staff.activity');
Route::get('staff/{id}/edit', [$staffAdmin, 'edit'])->name('staff.edit');
Route::post('staff/settings', [$staffAdmin, 'saveSettings'])->name('staff.settings');
Route::post('staff', [$staffAdmin, 'store'])->name('staff.store');
Route::put('staff/{id}', [$staffAdmin, 'update'])->name('staff.update');
Route::post('staff/{id}/toggle', [$staffAdmin, 'toggle'])->name('staff.toggle');
Route::post('staff/{id}/regenerate-code', [$staffAdmin, 'regenerateCode'])->name('staff.regenerate-code');
Route::post('staff/{id}/delete', [$staffAdmin, 'destroy'])->name('staff.delete');

$acct = \Plugins\AdminCore\src\Http\Controllers\AccountingController::class;
$adoc = \Plugins\AdminCore\src\Http\Controllers\AccountingDocumentController::class;
Route::get('accounting', [$acct, 'index'])->name('accounting');
Route::get('accounting/ledger', [$acct, 'ledger'])->name('accounting.ledger');
Route::get('accounting/create', [$acct, 'create'])->name('accounting.create');
Route::post('accounting', [$acct, 'store'])->name('accounting.store');
Route::post('accounting/{id}/delete', [$acct, 'destroy'])->name('accounting.delete');
Route::get('accounting/reports', [$acct, 'reports'])->name('accounting.reports');
Route::post('accounting/sync-orders', [$acct, 'syncOrders'])->name('accounting.sync');
Route::get('accounting/settings', [$acct, 'settings'])->name('accounting.settings');
Route::post('accounting/settings', [$acct, 'saveSettings'])->name('accounting.settings.save');

Route::get('accounting/documents', [$adoc, 'index'])->name('accounting.documents');
Route::get('accounting/documents/create', [$adoc, 'create'])->name('accounting.documents.create');
Route::post('accounting/documents/sync-orders', [$adoc, 'syncOrders'])->name('accounting.documents.sync');
Route::post('accounting/documents', [$adoc, 'store'])->name('accounting.documents.store');
Route::get('accounting/documents/{id}', [$adoc, 'show'])->name('accounting.documents.show');
Route::post('accounting/documents/{id}/send', [$adoc, 'send'])->name('accounting.documents.send');
Route::post('accounting/documents/{id}/confirm', [$adoc, 'confirm'])->name('accounting.documents.confirm');
Route::get('accounting/lookup/products', [$adoc, 'productLookup'])->name('accounting.lookup.products');
Route::get('accounting/lookup/customers', [$adoc, 'customerLookup'])->name('accounting.lookup.customers');

Route::get('reports', [Phase2Controller::class, 'reports'])->name('reports');
Route::post('reports/settings', [Phase2Controller::class, 'saveReportsSettings'])->name('reports.settings');
