<?php

use Illuminate\Support\Facades\Route;
use Modules\StorageGuard\Http\Controllers\StatusController;

// Keep middleware lightweight and self-authorize inside controller to avoid missing aliases.
Route::middleware(['web', 'auth', 'storageguard.ensure'])
    ->prefix('storage-guard')
    ->name('storageguard.')
    ->group(function () {
        Route::get('/status', [StatusController::class, 'index'])->name('status');
        Route::post('/fix', [StatusController::class, 'fix'])->name('fix');
    });
