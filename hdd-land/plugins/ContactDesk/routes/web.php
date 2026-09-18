<?php

use Illuminate\Support\Facades\Route;
use Plugins\ContactDesk\src\Http\Controllers\PageController;

if (! Route::has('contact')) {
    Route::get('/contact', [PageController::class, 'show'])->name('contact');
}
