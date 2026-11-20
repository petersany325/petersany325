<?php

use App\Http\Controllers\DesktopSyncV1Controller;
use App\Http\Middleware\EnsureDesktopSyncToken;
use App\Observers\DesktopSyncTombstoneObserver;
use Illuminate\Support\Facades\Route;

DesktopSyncTombstoneObserver::register();

Route::prefix('desktop-sync/v1')->group(function () {
    Route::middleware([EnsureDesktopSyncToken::class.':any', 'throttle:120,1'])->group(function () {
        Route::get('ping', [DesktopSyncV1Controller::class, 'ping'])->name('desktop-sync.ping');
    });
    Route::middleware([EnsureDesktopSyncToken::class.':read', 'throttle:120,1'])->group(function () {
        Route::get('changes', [DesktopSyncV1Controller::class, 'changes'])->name('desktop-sync.changes');
        Route::get('snapshot', [DesktopSyncV1Controller::class, 'snapshot'])->name('desktop-sync.snapshot');
        Route::get('status', [DesktopSyncV1Controller::class, 'status'])->name('desktop-sync.status');
    });
    Route::middleware([EnsureDesktopSyncToken::class.':write', 'throttle:120,1'])->group(function () {
        Route::post('push', [DesktopSyncV1Controller::class, 'push'])->name('desktop-sync.push');
    });
});
