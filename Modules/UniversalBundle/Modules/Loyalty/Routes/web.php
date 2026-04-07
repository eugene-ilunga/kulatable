<?php

use Illuminate\Support\Facades\Route;
use Modules\Loyalty\Http\Controllers\LoyaltyController;

Route::group(['prefix' => 'restaurant'], function () {
    Route::get('/my-loyalty/{hash}', [LoyaltyController::class, 'customerAccount'])->name('loyalty.customer.account');
});

Route::get('/my-loyalty', [LoyaltyController::class, 'customerAccountSubdomain'])->name('loyalty.customer.account.subdomain');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::prefix('loyalty')->name('loyalty.')->group(function () {
        Route::view('reports', 'loyalty::reports.index')->name('reports.index');
    });
});
