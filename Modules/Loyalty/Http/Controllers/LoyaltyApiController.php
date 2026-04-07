<?php

namespace Modules\Loyalty\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Customer;
use Illuminate\Http\Request;
use Modules\Loyalty\Services\LoyaltyService;
use Modules\Loyalty\Entities\LoyaltySetting;
use Illuminate\Support\Facades\Log;

class LoyaltyApiController extends Controller
{
    protected LoyaltyService $loyaltyService;

    public function __construct(LoyaltyService $loyaltyService)
    {
        $this->loyaltyService = $loyaltyService;
    }

    /**
     * Check if Loyalty module is in restaurant's package.
     */
    protected function checkModuleInPackage($restaurantId): bool
    {
        if (!function_exists('restaurant_modules')) {
            return false;
        }

        $restaurant = \App\Models\Restaurant::find($restaurantId);
        if (!$restaurant) {
            return false;
        }

        $restaurantModules = restaurant_modules($restaurant);
        return in_array('Loyalty', $restaurantModules);
    }

    /**
     * Get available points for a customer.
     */
    public function getAvailablePoints(Request $request)
    {
        try {
            $request->validate([
                'customer_id' => 'required|exists:customers,id',
            ]);

            $customer = Customer::findOrFail($request->customer_id);
            $restaurantId = restaurant()->id;

            // Check if module is in package
            if (!$this->checkModuleInPackage($restaurantId)) {
                return response()->json([
                    'success' => false,
                    'message' => __('loyalty::app.loyaltyModuleNotInPackage'),
                    'available_points' => 0,
                ], 403);
            }

            $availablePoints = $this->loyaltyService->getAvailablePoints($restaurantId, $customer->id);

            $settings = LoyaltySetting::getForRestaurant($restaurantId);
            $pointsValue = $availablePoints * $settings->value_per_point;

            return response()->json([
                'success' => true,
                'available_points' => $availablePoints,
                'points_value' => round($pointsValue, 2),
                'currency_symbol' => currency() ?? restaurant()->currency->currency_symbol ?? '',
            ]);
        } catch (\Exception $e) {
            Log::error('Loyalty API: Failed to get available points', [
                'error' => $e->getMessage(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => __('loyalty::app.failedToGetPoints'),
            ], 500);
        }
    }

    /**
     * Get loyalty information for checkout (points + max discount).
     */
    public function getCheckoutInfo(Request $request)
    {
        try {
            $request->validate([
                'customer_id' => 'required|exists:customers,id',
                'order_id' => 'nullable|exists:orders,id',
                'subtotal' => 'required|numeric|min:0',
            ]);

            $customer = Customer::findOrFail($request->customer_id);
            $restaurantId = restaurant()->id;

            // Check if module is in package
            if (!$this->checkModuleInPackage($restaurantId)) {
                return response()->json([
                    'success' => false,
                    'message' => __('loyalty::app.loyaltyModuleNotInPackage'),
                    'available_points' => 0,
                    'max_discount' => 0,
                ], 403);
            }

            $info = $this->loyaltyService->calculateMaxDiscount(
                $restaurantId,
                $customer->id,
                $request->subtotal
            );

            $settings = LoyaltySetting::getForRestaurant($restaurantId);

            return response()->json([
                'success' => true,
                'available_points' => $info['available_points'],
                'max_discount' => $info['max_discount'],
                'points_required' => $info['points_required'],
                'min_redeem_points' => $settings->min_redeem_points,
                'value_per_point' => $settings->value_per_point,
                'currency_symbol' => currency() ?? restaurant()->currency->currency_symbol ?? '',
                'enabled' => $settings->isEnabled(),
            ]);
        } catch (\Exception $e) {
            Log::error('Loyalty API: Failed to get checkout info', [
                'error' => $e->getMessage(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => __('loyalty::app.failedToGetCheckoutInfo'),
            ], 500);
        }
    }

    /**
     * Redeem points for an order.
     */
    public function redeemPoints(Request $request)
    {
        try {
            $request->validate([
                'order_id' => 'required|exists:orders,id',
                'points' => 'required|integer|min:1',
            ]);

            $order = Order::findOrFail($request->order_id);
            $restaurantId = restaurant()->id;

            // Verify order belongs to current restaurant
            if ($order->branch->restaurant_id !== $restaurantId) {
                return response()->json([
                    'success' => false,
                    'message' => __('loyalty::app.orderDoesNotBelongToRestaurant'),
                ], 403);
            }

            // Check if module is in package
            if (!$this->checkModuleInPackage($restaurantId)) {
                return response()->json([
                    'success' => false,
                    'message' => __('loyalty::app.loyaltyModuleNotInPackage'),
                ], 403);
            }

            $result = $this->loyaltyService->redeemPoints($order, $request->points);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'points_redeemed' => $result['points_redeemed'],
                    'discount_amount' => $result['discount_amount'],
                    'message' => $result['message'],
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'],
                ], 400);
            }
        } catch (\Exception $e) {
            Log::error('Loyalty API: Failed to redeem points', [
                'error' => $e->getMessage(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => __('loyalty::app.failedToRedeemPoints') . ': ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove redemption from an order.
     */
    public function removeRedemption(Request $request)
    {
        try {
            $request->validate([
                'order_id' => 'required|exists:orders,id',
            ]);

            $order = Order::findOrFail($request->order_id);
            $restaurantId = restaurant()->id;

            // Verify order belongs to current restaurant
            if ($order->branch->restaurant_id !== $restaurantId) {
                return response()->json([
                    'success' => false,
                    'message' => __('loyalty::app.orderDoesNotBelongToRestaurant'),
                ], 403);
            }

            // Check if module is in package
            if (!$this->checkModuleInPackage($restaurantId)) {
                return response()->json([
                    'success' => false,
                    'message' => __('loyalty::app.loyaltyModuleNotInPackage'),
                ], 403);
            }

            $result = $this->loyaltyService->removeRedemption($order);

            return response()->json([
                'success' => $result,
                'message' => $result ? __('loyalty::app.redemptionRemoved') : __('loyalty::app.noRedemptionToRemove'),
            ]);
        } catch (\Exception $e) {
            Log::error('Loyalty API: Failed to remove redemption', [
                'error' => $e->getMessage(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => __('loyalty::app.redemptionRemoveFailed'),
            ], 500);
        }
    }

    /**
     * Get customer points by phone number (for POS).
     */
    public function getPointsByPhone(Request $request)
    {
        try {
            $request->validate([
                'phone' => 'required|string',
                'phone_code' => 'nullable|string',
            ]);

            $restaurantId = restaurant()->id;

            // Check if module is in package
            if (!$this->checkModuleInPackage($restaurantId)) {
                return response()->json([
                    'success' => false,
                    'message' => __('loyalty::app.loyaltyModuleNotInPackage'),
                    'available_points' => 0,
                ], 403);
            }

            $customer = Customer::where('restaurant_id', $restaurantId)
                ->where('phone', $request->phone);

            if ($request->phone_code) {
                $customer->where('phone_code', $request->phone_code);
            }

            $customer = $customer->first();

            if (!$customer) {
                return response()->json([
                    'success' => false,
                    'message' => __('loyalty::app.customerNotFound'),
                    'available_points' => 0,
                ]);
            }

            $availablePoints = $this->loyaltyService->getAvailablePoints($restaurantId, $customer->id);
            $settings = LoyaltySetting::getForRestaurant($restaurantId);
            $pointsValue = $availablePoints * $settings->value_per_point;

            return response()->json([
                'success' => true,
                'customer_id' => $customer->id,
                'customer_name' => $customer->name,
                'available_points' => $availablePoints,
                'points_value' => round($pointsValue, 2),
                'currency_symbol' => currency() ?? restaurant()->currency->currency_symbol ?? '',
            ]);
        } catch (\Exception $e) {
            Log::error('Loyalty API: Failed to get points by phone', [
                'error' => $e->getMessage(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => __('loyalty::app.failedToGetPoints'),
            ], 500);
        }
    }
}

