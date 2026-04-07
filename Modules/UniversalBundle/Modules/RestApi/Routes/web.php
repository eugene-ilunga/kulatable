<?php

use Illuminate\Support\Facades\Route;
use Modules\RestApi\Http\Controllers\DocumentationController;

Route::middleware(['web'])
    ->prefix('application-integration')
    ->name('applicationintegration.')
    ->group(function () {
        Route::middleware(['auth'])->group(function () {
            Route::get('/docs', [DocumentationController::class, 'index'])->name('docs');
        });

        Route::get('/docs/public/{token}', [DocumentationController::class, 'public'])->name('docs.public');
    });

