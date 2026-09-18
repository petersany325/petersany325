<?php

use Illuminate\Support\Facades\Route;
use Plugins\AboutDesk\src\Http\Controllers\PageController;

if (! Route::has('about')) {
    Route::get('/about', [PageController::class, 'show'])->name('about');
}
