<?php

use App\Http\Controllers\AiChatController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('chat/{conversation?}', DashboardController::class)->name('chat');
    Route::post('ai/chat', AiChatController::class)->name('ai.chat');
    Route::get('ai/conversations/{conversation}/messages', [AiChatController::class, 'history']);
});

require __DIR__.'/settings.php';