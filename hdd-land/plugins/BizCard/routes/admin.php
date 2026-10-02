<?php

use Illuminate\Support\Facades\Route;
use Plugins\BizCard\src\Http\Controllers\Admin\CardController;

Route::get('biz-card', [CardController::class, 'edit'])->name('biz-card');
Route::post('biz-card', [CardController::class, 'update'])->name('biz-card.save');
Route::post('biz-card/send-link', [CardController::class, 'sendLink'])->name('biz-card.send-link');
Route::post('biz-card/send-club', [CardController::class, 'sendClub'])->name('biz-card.send-club');
Route::post('biz-card/members/{id}/confirm', [CardController::class, 'confirmMember'])->name('biz-card.members.confirm');
Route::post('biz-card/members/{id}/delete', [CardController::class, 'deleteMember'])->name('biz-card.members.delete');
