<?php

use Illuminate\Support\Facades\Route;
use Plugins\BizCard\src\Http\Controllers\PageController;

Route::get('/card', [PageController::class, 'show'])->name('biz-card.show');
Route::get('/card/vcard', [PageController::class, 'vcard'])->name('biz-card.vcard');
Route::post('/card/club', [PageController::class, 'club'])->name('biz-card.club');
Route::get('/card/club/confirm/{token}', [PageController::class, 'confirm'])
    ->where('token', '[A-Fa-f0-9]{16,64}')
    ->name('biz-card.club.confirm');
