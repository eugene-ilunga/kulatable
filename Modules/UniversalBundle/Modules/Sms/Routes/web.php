<?php

use Illuminate\Support\Facades\Route;
use Modules\Sms\Http\Controllers\SmsSettingController;
use App\Http\Middleware\SuperAdmin;
use Modules\Sms\Http\Controllers\SuperAdminSmsSettingController;

Route::middleware(['auth', config('jetstream.auth_session'), 'verified'])->prefix('sms')->group(function () {
    Route::resource('sms-settings', SmsSettingController::class);
});

Route::middleware(['auth', config('jetstream.auth_session'), 'verified', SuperAdmin::class])->group(function () {

    Route::name('superadmin.')->group(function () {
        Route::resource('superadmin-sms-settings', SuperAdminSmsSettingController::class);
    });
});
