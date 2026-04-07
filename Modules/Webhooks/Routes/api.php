<?php

use Illuminate\Support\Facades\Route;
use Modules\Webhooks\Http\Controllers\WebhookController;

Route::middleware(['auth:sanctum'])
    ->prefix('webhooks')
    ->name('webhooks.')
    ->group(function () {
        Route::get('/', [WebhookController::class, 'index'])->name('index');
        Route::post('/', [WebhookController::class, 'store'])->name('store');
        Route::put('/{webhook}', [WebhookController::class, 'update'])->name('update');
        Route::delete('/{webhook}', [WebhookController::class, 'destroy'])->name('destroy');
        Route::post('/{webhook}/test', [WebhookController::class, 'sendTest'])->name('test');
        Route::post('/deliveries/{delivery}/replay', [WebhookController::class, 'replay'])->name('deliveries.replay');
        Route::get('/deliveries/{delivery}', [WebhookController::class, 'showDelivery'])->name('deliveries.show');
    });
