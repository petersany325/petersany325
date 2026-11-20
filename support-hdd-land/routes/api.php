<?php

use App\Http\Controllers\DesktopSyncController;
use App\Http\Controllers\DesktopSyncV1Controller;
use App\Http\Middleware\AuthenticateDesktopDevice;
use App\Http\Middleware\EnsureDesktopSyncToken;
use Illuminate\Support\Facades\Route;

Route::prefix('desktop/v1')->middleware('throttle:120,1')->group(function () {
    Route::middleware(AuthenticateDesktopDevice::class)->group(function () {
        Route::get('spec', [DesktopSyncController::class, 'spec'])->name('desktop.spec');
        Route::get('ping', [DesktopSyncController::class, 'ping'])->name('desktop.ping');
        Route::post('sync', [DesktopSyncController::class, 'sync'])->name('desktop.sync');
    });
});

Route::prefix('desktop-sync/v1')->group(function () {
    Route::middleware([EnsureDesktopSyncToken::class.':any', 'throttle:desktop-sync'])->group(function () {
        Route::get('ping', [DesktopSyncV1Controller::class, 'ping'])->name('desktop-sync.ping');
    });
    Route::middleware([EnsureDesktopSyncToken::class.':read', 'throttle:desktop-sync'])->group(function () {
        Route::get('changes', [DesktopSyncV1Controller::class, 'changes'])->name('desktop-sync.changes');
        Route::get('snapshot', [DesktopSyncV1Controller::class, 'snapshot'])->name('desktop-sync.snapshot');
        Route::get('status', [DesktopSyncV1Controller::class, 'status'])->name('desktop-sync.status');
    });
    Route::middleware([EnsureDesktopSyncToken::class.':write', 'throttle:desktop-sync'])->group(function () {
        Route::post('push', [DesktopSyncV1Controller::class, 'push'])->name('desktop-sync.push');
    });
});
