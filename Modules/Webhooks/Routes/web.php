<?php

use Illuminate\Support\Facades\Route;
use Modules\Webhooks\Http\Controllers\WebhookController;
use Modules\Webhooks\Livewire\SuperAdmin\Dashboard;
use Modules\Webhooks\Livewire\SuperAdmin\Setting;
use Modules\Webhooks\Livewire\SuperAdmin\RoutingMatrix;
use Modules\Webhooks\Livewire\SuperAdmin\PackageDefaults;
use Modules\Webhooks\Livewire\SuperAdmin\SystemWebhooks;

Route::middleware(['web', 'auth', config('jetstream.auth_session'), 'verified'])
    ->prefix('webhooks')
    ->name('webhooks.')
    ->group(function () {
        // Tenant Admin routes (permission + module guard inside controller)
        Route::get('/', [WebhookController::class, 'index'])->name('index');
        Route::post('/', [WebhookController::class, 'store'])->name('store');
        Route::put('/{webhook}', [WebhookController::class, 'update'])->name('update');
        Route::delete('/{webhook}', [WebhookController::class, 'destroy'])->name('destroy');
        Route::post('/{webhook}/test', [WebhookController::class, 'sendTest'])->name('test');
        Route::post('/deliveries/{delivery}/replay', [WebhookController::class, 'replay'])->name('deliveries.replay');
        Route::get('/deliveries/{delivery}', [WebhookController::class, 'showDelivery'])->name('deliveries.show');
    });

// Super Admin routes (guarded in middleware stack)
Route::middleware(['web', 'auth', config('jetstream.auth_session'), 'verified', \App\Http\Middleware\SuperAdmin::class])
    ->prefix('super-admin/webhooks')
    ->name('superadmin.webhooks.')
    ->group(function () {
        Route::get('/dashboard', Dashboard::class)->name('dashboard');
        Route::get('/settings', Setting::class)->name('settings');
        Route::get('/routing', RoutingMatrix::class)->name('routing-matrix');
        Route::get('/package-defaults', PackageDefaults::class)->name('package-defaults');
        Route::get('/system', SystemWebhooks::class)->name('system'); // System-level webhooks
    });

