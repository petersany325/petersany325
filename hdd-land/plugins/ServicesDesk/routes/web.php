<?php

use Illuminate\Support\Facades\Route;
use Plugins\ServicesDesk\src\Http\Controllers\PageController;

if (! Route::has('services')) {
    Route::get('/services', [PageController::class, 'show'])->name('services');
}
