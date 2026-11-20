<?php

use App\Http\Controllers\DesktopSyncController;
use App\Http\Middleware\AuthenticateDesktopDevice;
use Illuminate\Support\Facades\Route;

Route::prefix('desktop/v1')->middleware('throttle:120,1')->group(function () {
    Route::middleware(AuthenticateDesktopDevice::class)->group(function () {
        Route::get('spec', [DesktopSyncController::class, 'spec'])->name('desktop.spec');
        Route::get('ping', [DesktopSyncController::class, 'ping'])->name('desktop.ping');
        Route::post('sync', [DesktopSyncController::class, 'sync'])->name('desktop.sync');
    });
});
