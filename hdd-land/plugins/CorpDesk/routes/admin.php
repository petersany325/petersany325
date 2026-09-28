<?php

use Illuminate\Support\Facades\Route;
use Plugins\CorpDesk\src\Http\Controllers\Admin\CorpController;

Route::get('/corp-pages', [CorpController::class, 'edit'])->defaults('page', 'enterprise');
Route::get('/corp-pages/{page}', [CorpController::class, 'edit'])->where('page', 'enterprise|cctv');
Route::post('/corp-pages/{page}', [CorpController::class, 'save'])->where('page', 'enterprise|cctv');
