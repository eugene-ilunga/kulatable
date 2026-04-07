<?php

use Illuminate\Support\Facades\Route;
use Modules\Whatsapp\Http\Controllers\WhatsAppWebhookController;
use Modules\Whatsapp\Http\Middleware\CheckWhatsAppModuleEnabled;

// Webhook routes (no auth required - Meta will call these)
// These routes are accessible without authentication for webhook verification
Route::prefix('whatsapp/webhook')->name('whatsapp.webhook.')->group(function () {
    // GET: Webhook verification (Meta calls this to verify the webhook URL)
    // POST: Also accepts POST requests (Meta may send webhook events here)
    Route::match(['GET', 'POST'], 'verify', [WhatsAppWebhookController::class, 'verify'])->name('verify');
    // POST: Webhook events (Meta sends message status updates here)
    Route::post('handle', [WhatsAppWebhookController::class, 'handle'])->name('handle');
});

Route::middleware(['auth', 'verified', CheckWhatsAppModuleEnabled::class])->group(function () {
    // WhatsApp Settings - only accessible if module is enabled
    Route::prefix('whatsapp')->name('whatsapp.')->group(function () {
        Route::get('settings', function () {
            // Settings page - to be implemented
            return view('whatsapp::settings.index');
        })->name('settings');
        
        Route::get('templates', function () {
            // Template library page - to be implemented
            return view('whatsapp::templates.index');
        })->name('templates');
        
        Route::get('mappings', function () {
            // Template mapping page - to be implemented
            return view('whatsapp::mappings.index');
        })->name('mappings');
        
        Route::get('logs', function () {
            // Notification logs page - to be implemented
            return view('whatsapp::logs.index');
        })->name('logs');
    });
});
