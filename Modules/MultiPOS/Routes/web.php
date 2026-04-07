<?php

use Illuminate\Support\Facades\Route;
use Modules\MultiPOS\Http\Controllers\MultiPOSController;
use Modules\MultiPOS\Http\Controllers\ClaimMachineController;
use Modules\MultiPOS\Http\Controllers\PosMachineController;
use Modules\MultiPOS\Http\Controllers\PosMachineReportController;

Route::middleware(['auth', 'verified'])->group(function () {
    // POS Machine Registration Routes
    Route::prefix('pos')->name('pos.')->group(function () {
        Route::get('/claim', [ClaimMachineController::class, 'create'])->name('claim');
        Route::post('/claim', [ClaimMachineController::class, 'store'])->name('claim.store');
        Route::get('/claim/check', [ClaimMachineController::class, 'check'])->name('claim.check');
        Route::post('/claim/check-branch-limit', [ClaimMachineController::class, 'checkBranchLimit'])->name('claim.check-branch-limit');
    });

    // MultiPOS Management Routes
    Route::prefix('multi-pos')->name('multi-pos.')->group(function () {
        Route::get('/', [MultiPOSController::class, 'index'])->name('index');
        Route::get('/terminals', [MultiPOSController::class, 'terminals'])->name('terminals');
        Route::get('/settings', [MultiPOSController::class, 'settings'])->name('settings');

        // POS Machine Management Routes
        Route::prefix('machines')->name('machines.')->group(function () {
            Route::get('/', [PosMachineController::class, 'index'])->name('index');
            Route::get('/pending', [PosMachineController::class, 'pending'])->name('pending');
            Route::post('/{id}/approve', [PosMachineController::class, 'approve'])->name('approve');
            Route::post('/{id}/disable', [PosMachineController::class, 'disable'])->name('disable');
            Route::put('/{id}', [PosMachineController::class, 'update'])->name('update');
            Route::delete('/{id}', [PosMachineController::class, 'destroy'])->name('destroy');
            Route::get('/{id}/statistics', [PosMachineController::class, 'statistics'])->name('statistics');
            Route::post('/{id}/rotate-token', [PosMachineController::class, 'rotateToken'])->name('rotate-token');
        });

        // POS Machine Report Routes
        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('/sales-summary', \Modules\MultiPOS\Livewire\Reports\PosMachineSalesSummary::class)->name('sales-summary');
            Route::get('/export-csv', [PosMachineReportController::class, 'exportCSV'])->name('export-csv');
        });
    });
});
