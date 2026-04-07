<?php

use Illuminate\Support\Facades\Route;
use Modules\MultiPOS\Http\Controllers\MultiPOSController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::prefix('multi-pos')->name('api.multi-pos.')->group(function () {
        Route::get('/terminals', [MultiPOSController::class, 'apiTerminals'])->name('terminals');
        Route::post('/terminals', [MultiPOSController::class, 'storeTerminal'])->name('store-terminal');
        Route::put('/terminals/{id}', [MultiPOSController::class, 'updateTerminal'])->name('update-terminal');
        Route::delete('/terminals/{id}', [MultiPOSController::class, 'deleteTerminal'])->name('delete-terminal');
    });
});
