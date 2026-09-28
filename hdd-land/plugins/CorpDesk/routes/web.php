<?php

use Illuminate\Support\Facades\Route;
use Plugins\CorpDesk\src\Http\Controllers\PageController;

Route::get('/enterprise-storage', [PageController::class, 'enterprise']);
Route::get('/org-hdd', [PageController::class, 'enterprise']);
Route::get('/cctv-projects', [PageController::class, 'cctv']);
Route::get('/cctv', [PageController::class, 'cctv']);
