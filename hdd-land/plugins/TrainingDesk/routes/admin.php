<?php

use Illuminate\Support\Facades\Route;
use Plugins\TrainingDesk\src\Http\Controllers\Admin\TrainingController;

Route::get('training-page', [TrainingController::class, 'edit'])->name('training-page.edit');
Route::post('training-page', [TrainingController::class, 'save'])->name('training-page.save');
