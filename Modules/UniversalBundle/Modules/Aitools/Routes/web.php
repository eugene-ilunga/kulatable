<?php

use Illuminate\Support\Facades\Route;
use Modules\Aitools\Http\Controllers\AitoolsController;
use Modules\Aitools\Livewire\Ai\Chat;

Route::middleware(['web', 'auth', 'verified'])->group(function () {
    Route::resource('aitools', AitoolsController::class)->names('aitools');

    // AI Chat routes
    Route::get('ai', Chat::class)->name('ai.chat');
    Route::get('ai/conversation/{conversationId}', Chat::class)->name('ai.conversation');
});
