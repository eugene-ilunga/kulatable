<?php

use Illuminate\Support\Facades\Route;
use Modules\Aitools\Http\Controllers\AitoolsController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('aitools', AitoolsController::class)->names('aitools');
});
