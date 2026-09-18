<?php

use Illuminate\Support\Facades\Route;
use Plugins\ContactDesk\src\Http\Controllers\Admin\ContactController;

Route::get('contact-page', [ContactController::class, 'edit'])->name('contact-page.edit');
Route::post('contact-page', [ContactController::class, 'save'])->name('contact-page.save');
