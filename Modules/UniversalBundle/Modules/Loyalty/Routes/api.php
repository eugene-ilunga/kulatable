<?php

use Illuminate\Support\Facades\Route;
use Modules\Loyalty\Http\Controllers\LoyaltyApiController;

// POS API routes (requires auth)
Route::middleware(['auth', config('jetstream.auth_session'), 'verified'])->prefix('pos')->group(function () {
    Route::get('/loyalty/points-by-phone', [LoyaltyApiController::class, 'getPointsByPhone'])->name('api.pos.loyalty.points-by-phone');
    Route::get('/loyalty/checkout-info', [LoyaltyApiController::class, 'getCheckoutInfo'])->name('api.pos.loyalty.checkout-info');
    Route::post('/loyalty/redeem', [LoyaltyApiController::class, 'redeemPoints'])->name('api.pos.loyalty.redeem');
    Route::post('/loyalty/remove-redemption', [LoyaltyApiController::class, 'removeRedemption'])->name('api.pos.loyalty.remove-redemption');
});

// Customer site/Kiosk API routes (public, but should validate restaurant hash)
Route::prefix('loyalty')->group(function () {
    Route::get('/checkout-info', [LoyaltyApiController::class, 'getCheckoutInfo'])->name('api.loyalty.checkout-info');
    Route::post('/redeem', [LoyaltyApiController::class, 'redeemPoints'])->name('api.loyalty.redeem');
    Route::post('/remove-redemption', [LoyaltyApiController::class, 'removeRedemption'])->name('api.loyalty.remove-redemption');
});
