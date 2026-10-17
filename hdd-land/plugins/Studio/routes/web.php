<?php

use Illuminate\Support\Facades\Route;
use Plugins\Studio\src\Http\Controllers\StudioController;

Route::get('/design', [StudioController::class, 'design'])->name('studio.design');
Route::get('/ai', [StudioController::class, 'ai'])->name('studio.ai');
Route::get('/accounting', [StudioController::class, 'accounting'])->name('studio.accounting');
Route::get('/projects', [StudioController::class, 'projects'])->name('studio.projects');
Route::get('/receipt-software', [StudioController::class, 'receiptSoftware'])->name('studio.receipt');
