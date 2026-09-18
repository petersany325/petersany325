<?php

use Illuminate\Support\Facades\Route;
use Plugins\TrainingDesk\src\Http\Controllers\PageController;

if (! Route::has('training')) {
    Route::get('/training', [PageController::class, 'hub'])->name('training');
}
if (! Route::has('training.show')) {
    Route::get('/training/{slug}', [PageController::class, 'show'])->name('training.show');
}
