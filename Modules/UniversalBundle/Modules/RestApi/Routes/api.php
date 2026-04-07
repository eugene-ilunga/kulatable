<?php

use Illuminate\Support\Facades\Route;
use Modules\RestApi\Http\Controllers\AppWideController;
use Modules\RestApi\Http\Controllers\AuthController;
use Modules\RestApi\Http\Controllers\CashRegisterController;
use Modules\RestApi\Http\Controllers\CustomerController;
use Modules\RestApi\Http\Controllers\MultiPosIntegrationController;
use Modules\RestApi\Http\Controllers\DeviceController;
use Modules\RestApi\Http\Controllers\NotificationController;
use Modules\RestApi\Http\Controllers\PlatformController;
use Modules\RestApi\Http\Controllers\PosProxyController;
use Modules\RestApi\Http\Controllers\PusherController;
use Modules\RestApi\Http\Controllers\PartnerAuthController;
use Modules\RestApi\Http\Controllers\PartnerController;
use Modules\RestApi\Http\Middleware\EnsurePosFeatureEnabled;
use Modules\RestApi\Http\Middleware\VerifyPartnerUniqueCode;

Route::middleware('api')->prefix('api/application-integration')->group(function () {
    // Auth endpoints for apps - rate limited to prevent brute force attacks
    Route::middleware(['throttle:5,1'])->post('/auth/login', [AuthController::class, 'login']);


    // ══════════════════════════════════════════════════════════════════════════
    // PUSHER ENDPOINTS - System-wide, accessible to ALL authenticated users
    // These are NOT behind EnsurePosFeatureEnabled because Pusher settings
    // are configured once by superadmin and used by all users (admin, staff, etc.)
    // ══════════════════════════════════════════════════════════════════════════
    Route::middleware('auth:sanctum')->prefix('pusher')->group(function () {
        Route::get('/settings', [PusherController::class, 'getPusherSettings']);
        Route::get('/broadcast-settings', [PusherController::class, 'getPusherBroadcastSettings']);
        Route::get('/beams-settings', [PusherController::class, 'getPusherBeamsSettings']);
        Route::get('/status', [PusherController::class, 'checkPusherStatus']);
        Route::post('/authorize-channel', [PusherController::class, 'authorizeChannel']);
        Route::get('/presence/{channel}/members', [PusherController::class, 'getPresenceChannelMembers']);
        // Diagnostics endpoint - only for debugging
        Route::get('/diagnostics', [PusherController::class, 'diagnostics']);
    });

    Route::middleware(['auth:sanctum', EnsurePosFeatureEnabled::class])->group(function () {
        Route::get('/auth/me', [AuthController::class, 'me']);

        // Platform/system wide
        Route::prefix('platform')->group(function () {
            Route::get('/config', [PlatformController::class, 'config']);
            Route::get('/permissions', [PlatformController::class, 'permissions']);
            Route::get('/printers', [PlatformController::class, 'printers']);
            Route::get('/receipt-settings', [PlatformController::class, 'receiptSettings']);
            Route::post('/switch-branch', [PlatformController::class, 'switchBranch']);
        });

        // Shared/global
        Route::get('/languages', [AppWideController::class, 'languages']);
        Route::get('/currencies', [AppWideController::class, 'currencies']);
        Route::get('/payment-gateways', [AppWideController::class, 'paymentGateways']);
        Route::get('/staff', [AppWideController::class, 'staff']);
        Route::get('/roles', [AppWideController::class, 'roles']);
        Route::get('/areas', [AppWideController::class, 'areas']);
        Route::get('/customer-addresses', [AppWideController::class, 'customerAddresses']);
        Route::post('/customer-addresses', [AppWideController::class, 'storeCustomerAddress']);
        Route::put('/customer-addresses/{id}', [AppWideController::class, 'updateCustomerAddress']);
        Route::delete('/customer-addresses/{id}', [AppWideController::class, 'deleteCustomerAddress']);

        // Customer app endpoints (delivery/dine-in)
        Route::prefix('customer')->group(function () {
            Route::get('/catalog', [CustomerController::class, 'catalog']);
            Route::post('/orders', [CustomerController::class, 'placeOrder']);
            Route::get('/orders', [CustomerController::class, 'myOrders']);
        });

        // POS endpoints proxied to existing controller
        Route::prefix('pos')->group(function () {
            Route::get('/menus', [PosProxyController::class, 'getMenus']);
            Route::get('/categories', [PosProxyController::class, 'getCategories']);
            Route::get('/items', [PosProxyController::class, 'getMenuItems']);
            Route::get('/items/category/{categoryId}', [PosProxyController::class, 'getMenuItemsByCategory']);
            Route::get('/items/menu/{menuId}', [PosProxyController::class, 'getMenuItemsByMenu']);
            Route::get('/items/{itemId}/variations', [PosProxyController::class, 'getMenuItemVariations']);
            Route::get('/items/{itemId}/modifier-groups', [PosProxyController::class, 'getMenuItemModifierGroups']);
            Route::get('/extra-charges/{orderType}', [PosProxyController::class, 'getExtraCharges']);
            Route::get('/tables', [PosProxyController::class, 'getTables']);
            Route::post('/tables/{tableId}/unlock', [PosProxyController::class, 'forceUnlockTable']);
            Route::get('/reservations/today', [PosProxyController::class, 'getTodayReservations']);
            Route::get('/order-types', [PosProxyController::class, 'getOrderTypes']);
            Route::get('/actions', [PosProxyController::class, 'getActions']);
            Route::get('/delivery-platforms', [PosProxyController::class, 'getDeliveryPlatforms']);
            Route::get('/get-order-number', [PosProxyController::class, 'getOrderNumber']);
            Route::post('/orders', [PosProxyController::class, 'submitOrder']);
            Route::put('/orders/{id}', [PosProxyController::class, 'updateOrder']);  // Update existing order
            Route::get('/orders', [PosProxyController::class, 'getOrders']);
            Route::get('/orders/{id}', [PosProxyController::class, 'getOrder']);
            Route::post('/orders/{id}/status', [PosProxyController::class, 'updateOrderStatus']);
            Route::post('/orders/{id}/tip', [PosProxyController::class, 'addTip']);
            Route::post('/orders/{id}/split-payments', [PosProxyController::class, 'addSplitPayment']);
            Route::post('/orders/{id}/pay', [PosProxyController::class, 'payOrder']);

            // New: Update order items and create KOT
            Route::put('/orders/{id}/items', [PosProxyController::class, 'updateOrderItems']);
            Route::post('/orders/{id}/kot', [PosProxyController::class, 'createKot']);
            Route::get('/orders/{id}/kots', [PosProxyController::class, 'getOrderKots']);

            // KOT Management endpoints
            Route::get('/kots', [PosProxyController::class, 'getKots']);
            Route::get('/kots/{id}', [PosProxyController::class, 'getKot']);
            Route::put('/kots/{id}/status', [PosProxyController::class, 'updateKotStatus']);
            Route::put('/kot-items/{id}/status', [PosProxyController::class, 'updateKotItemStatus']);
            Route::get('/kot-places', [PosProxyController::class, 'getKotPlaces']);
            Route::get('/kot-cancel-reasons', [PosProxyController::class, 'getKotCancelReasons']);

            Route::get('/customers', [PosProxyController::class, 'getCustomers']);
            Route::get('/phone-codes', [PosProxyController::class, 'getPhoneCodes']);
            Route::post('/customers', [PosProxyController::class, 'saveCustomer']);
            Route::get('/taxes', [PosProxyController::class, 'getTaxes']);
            Route::get('/restaurants', [PosProxyController::class, 'getRestaurants']);
            Route::get('/branches', [PosProxyController::class, 'getBranches']);
            Route::get('/reservations', [PosProxyController::class, 'listReservations']);
            Route::post('/reservations', [PosProxyController::class, 'createReservation']);
            Route::post('/reservations/{id}/status', [PosProxyController::class, 'updateReservationStatus']);
            Route::get('/waiters', [PosProxyController::class, 'getWaiters']);
            Route::get('/delivery-executives', [PosProxyController::class, 'getDeliveryExecutives']);

            // Delivery Management endpoints
            Route::get('/delivery-settings', [PosProxyController::class, 'getDeliverySettings']);
            Route::post('/delivery-fee/calculate', [PosProxyController::class, 'calculateDeliveryFee']);
            Route::get('/delivery-fee-tiers', [PosProxyController::class, 'getDeliveryFeeTiers']);
            Route::get('/delivery-platforms/{id}', [PosProxyController::class, 'getDeliveryPlatform']);
            Route::post('/delivery-platforms', [PosProxyController::class, 'createDeliveryPlatform']);
            Route::put('/delivery-platforms/{id}', [PosProxyController::class, 'updateDeliveryPlatform']);
            Route::delete('/delivery-platforms/{id}', [PosProxyController::class, 'deleteDeliveryPlatform']);
            Route::post('/delivery-executives', [PosProxyController::class, 'createDeliveryExecutive']);
            Route::put('/delivery-executives/{id}', [PosProxyController::class, 'updateDeliveryExecutive']);
            Route::delete('/delivery-executives/{id}', [PosProxyController::class, 'deleteDeliveryExecutive']);
            Route::put('/delivery-executives/{id}/status', [PosProxyController::class, 'updateDeliveryExecutiveStatus']);
            Route::put('/orders/{id}/assign-delivery', [PosProxyController::class, 'assignDeliveryExecutive']);
            Route::put('/orders/{id}/delivery-status', [PosProxyController::class, 'updateDeliveryOrderStatus']);
            Route::get('/delivery-orders', [PosProxyController::class, 'getDeliveryOrders']);

            // Notifications (push + in-app)
            Route::post('/notifications/register-token', [NotificationController::class, 'registerToken']);
            Route::get('/notifications', [NotificationController::class, 'list']);
            Route::post('/notifications/{id}/read', [NotificationController::class, 'markRead']);
            Route::post('/notifications/test', [NotificationController::class, 'sendTest']);

            // MultiPOS device registration (optional; only works if MultiPOS module is enabled)
            Route::post('/multi-pos/register', [MultiPosIntegrationController::class, 'register']);
            Route::get('/multi-pos/check', [MultiPosIntegrationController::class, 'check']);

            // Cash Register Management
            Route::prefix('cash-register')->group(function () {
                // Registers
                Route::get('/registers', [CashRegisterController::class, 'getRegisters']);
                Route::get('/registers/{id}', [CashRegisterController::class, 'getRegister']);
                Route::post('/registers', [CashRegisterController::class, 'createRegister']);
                Route::put('/registers/{id}', [CashRegisterController::class, 'updateRegister']);
                Route::delete('/registers/{id}', [CashRegisterController::class, 'deleteRegister']);

                // Sessions
                Route::get('/sessions', [CashRegisterController::class, 'getSessions']);
                Route::get('/sessions/active', [CashRegisterController::class, 'getActiveSession']);
                Route::get('/sessions/{id}', [CashRegisterController::class, 'getSession']);
                Route::post('/sessions/open', [CashRegisterController::class, 'openSession']);
                Route::post('/sessions/{id}/close', [CashRegisterController::class, 'closeSession']);
                Route::get('/sessions/{id}/summary', [CashRegisterController::class, 'getSessionSummary']);
                Route::get('/sessions/{id}/transactions', [CashRegisterController::class, 'getTransactions']);

                // Transactions
                Route::post('/transactions/cash-in', [CashRegisterController::class, 'recordCashIn']);
                Route::post('/transactions/cash-out', [CashRegisterController::class, 'recordCashOut']);
                Route::post('/transactions/safe-drop', [CashRegisterController::class, 'recordSafeDrop']);

                // Denominations
                Route::get('/denominations', [CashRegisterController::class, 'getDenominations']);
                Route::get('/denominations/{uuid}', [CashRegisterController::class, 'getDenomination']);
                Route::post('/denominations', [CashRegisterController::class, 'createDenomination']);
                Route::put('/denominations/{uuid}', [CashRegisterController::class, 'updateDenomination']);
                Route::delete('/denominations/{uuid}', [CashRegisterController::class, 'deleteDenomination']);
            });

            // Customer delete endpoint
            Route::delete('/customers/{id}', [PosProxyController::class, 'deleteCustomer']);
        });

    });

    // ══════════════════════════════════════════════════════════════════════════
    // DELIVERY PARTNER API ENDPOINTS
    // These endpoints are for delivery partners to manage their orders
    // ══════════════════════════════════════════════════════════════════════════

    // Partner authentication endpoints (No middleware required)
    Route::prefix('partner/auth')->group(function () {
        Route::post('/login', [PartnerAuthController::class, 'login']);
        Route::post('/logout', [PartnerAuthController::class, 'logout'])
            ->middleware(VerifyPartnerUniqueCode::class);
    });

    // Partner endpoints (require unique code verification)
    Route::middleware([VerifyPartnerUniqueCode::class])->prefix('partner')->group(function () {
        // Profile
        Route::get('/profile', [PartnerController::class, 'getProfile']);

        // Orders
        Route::prefix('orders')->group(function () {
            Route::get('/latest', [PartnerController::class, 'getLatestOrder']);
            Route::get('/history', [PartnerController::class, 'getOrderHistory']);
            Route::post('/{id}/start', [PartnerController::class, 'startOrder']);
            Route::post('/{id}/status', [PartnerController::class, 'updateOrderStatus']);
        });

        // Location tracking (store partner lat/long with order, restaurant, branch)
        Route::post('/location', [PartnerController::class, 'storeLocation']);

        // FCM token for push notifications (order assigned, cancelled, ready for pickup)
        Route::post('/fcm-token', [PartnerController::class, 'registerFcmToken']);

        // Device registration for FCM notifications
        Route::post('/devices/register', [DeviceController::class, 'register']);
        Route::post('/devices/unregister', [DeviceController::class, 'unregister']);

    });
});
    