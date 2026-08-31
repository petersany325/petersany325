<?php

use Illuminate\Support\Facades\Route;
use Plugins\AuthCustomers\src\Http\Controllers\Admin\AuthSettingsController;
use Plugins\AuthCustomers\src\Http\Controllers\Admin\CustomersController;

Route::get('auth-settings', [AuthSettingsController::class, 'index'])->name('auth-settings');
Route::post('auth-settings', [AuthSettingsController::class, 'save'])->name('auth-settings.save');
Route::post('auth-settings/test-sms', [AuthSettingsController::class, 'testSms'])->name('auth-settings.test-sms');

Route::get('wallet-settings', [\Plugins\AuthCustomers\src\Http\Controllers\Admin\WalletAdminController::class, 'settings'])->name('wallet-settings');
Route::post('wallet-settings', [\Plugins\AuthCustomers\src\Http\Controllers\Admin\WalletAdminController::class, 'saveSettings'])->name('wallet-settings.save');
Route::post('customers/{user}/wallet', [\Plugins\AuthCustomers\src\Http\Controllers\Admin\WalletAdminController::class, 'adjust'])->name('customers.wallet');

Route::get('customers', [CustomersController::class, 'index'])->name('customers.index');
Route::get('customers/{user}/edit', [CustomersController::class, 'edit'])->name('customers.edit')->whereNumber('user');
Route::put('customers/{user}', [CustomersController::class, 'update'])->name('customers.update')->whereNumber('user');
Route::delete('customers/{user}', [CustomersController::class, 'destroy'])->name('customers.destroy')->whereNumber('user');
Route::post('customers/{user}/toggle-2fa', [CustomersController::class, 'toggle2fa'])->name('customers.toggle-2fa')->whereNumber('user');

Route::get('tickets', [\Plugins\AuthCustomers\src\Http\Controllers\Admin\TicketAdminController::class, 'index'])->name('tickets.index');
Route::get('tickets/create', [\Plugins\AuthCustomers\src\Http\Controllers\Admin\TicketAdminController::class, 'create'])->name('tickets.create');
Route::post('tickets', [\Plugins\AuthCustomers\src\Http\Controllers\Admin\TicketAdminController::class, 'store'])->name('tickets.store');
Route::get('tickets/settings', [\Plugins\AuthCustomers\src\Http\Controllers\Admin\TicketAdminController::class, 'settings'])->name('tickets.settings');
Route::post('tickets/settings', [\Plugins\AuthCustomers\src\Http\Controllers\Admin\TicketAdminController::class, 'saveSettings'])->name('tickets.settings.save');
Route::post('tickets/departments', [\Plugins\AuthCustomers\src\Http\Controllers\Admin\TicketAdminController::class, 'storeDepartment'])->name('tickets.departments.store');
Route::post('tickets/departments/{id}', [\Plugins\AuthCustomers\src\Http\Controllers\Admin\TicketAdminController::class, 'updateDepartment'])->name('tickets.departments.update')->whereNumber('id');
Route::delete('tickets/departments/{id}', [\Plugins\AuthCustomers\src\Http\Controllers\Admin\TicketAdminController::class, 'deleteDepartment'])->name('tickets.departments.delete')->whereNumber('id');
Route::get('tickets/{ticket}', [\Plugins\AuthCustomers\src\Http\Controllers\Admin\TicketAdminController::class, 'show'])->name('tickets.show')->whereNumber('ticket');
Route::post('tickets/{ticket}/reply', [\Plugins\AuthCustomers\src\Http\Controllers\Admin\TicketAdminController::class, 'reply'])->name('tickets.reply')->whereNumber('ticket');
Route::post('tickets/{ticket}/update', [\Plugins\AuthCustomers\src\Http\Controllers\Admin\TicketAdminController::class, 'update'])->name('tickets.update')->whereNumber('ticket');

Route::redirect('auth-register', '/admin/auth-settings?tab=register')->name('auth-register');
Route::redirect('auth-fields', '/admin/auth-settings?tab=fields')->name('auth-fields');
Route::redirect('auth-terms', '/admin/auth-settings?tab=terms')->name('auth-terms');
Route::redirect('auth-2fa', '/admin/auth-settings?tab=2fa')->name('auth-2fa');
Route::redirect('auth-sms', '/admin/auth-settings?tab=sms')->name('auth-sms');
Route::redirect('auth-notify', '/admin/auth-settings?tab=notify')->name('auth-notify');
