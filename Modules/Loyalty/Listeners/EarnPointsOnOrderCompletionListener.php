<?php

namespace Modules\Loyalty\Listeners;

use App\Events\SendNewOrderReceived;
use Modules\Loyalty\Services\LoyaltyService;
use Illuminate\Support\Facades\Log;

class EarnPointsOnOrderCompletionListener
{
    protected LoyaltyService $loyaltyService;

    public function __construct(LoyaltyService $loyaltyService)
    {
        $this->loyaltyService = $loyaltyService;
    }

    /**
     * Handle the event.
     */
    public function handle(SendNewOrderReceived $event): void
    {
        try {
            $order = $event->order;

            // Only process if order is paid and has a customer
            if ($order->status !== 'paid' || !$order->customer_id) {
                return;
            }

            $restaurantId = $order->branch->restaurant_id ?? null;
            
            // Check if loyalty module is enabled for this restaurant
            if (function_exists('restaurant_modules') && $restaurantId) {
                $restaurant = $order->branch->restaurant ?? \App\Models\Restaurant::find($restaurantId);
                if ($restaurant) {
                    $restaurantModules = restaurant_modules($restaurant);
                    if (!in_array('Loyalty', $restaurantModules)) {
                        return;
                    }
                }
            }
            
            if (!$restaurantId) {
                return;
            }

            // Get loyalty settings to check what's enabled
            $settings = \Modules\Loyalty\Entities\LoyaltySetting::getForRestaurant($restaurantId);
            
            // Check loyalty_type setting (points, stamps, or both)
            $loyaltyType = $settings->loyalty_type ?? 'points';
            
            // Determine order source/platform (POS, Customer Site, or Kiosk)
            $orderSource = $this->detectOrderSource($order);
            
            // Check if new platform fields exist
            $hasNewFields = !is_null($settings->enable_points_for_pos) && !is_null($settings->enable_stamps_for_pos);
            
            // Earn points only if:
            // 1. Points are enabled (enable_points = true)
            // 2. Loyalty type allows points (points or both)
            // 3. Points are enabled for the order's platform
            if ($settings->enable_points && in_array($loyaltyType, ['points', 'both'])) {
                $pointsEnabledForPlatform = false;
                
                if ($hasNewFields) {
                    // Use new platform-specific fields
                    switch ($orderSource) {
                        case 'pos':
                            $pointsEnabledForPlatform = $settings->enable_points_for_pos === true;
                            break;
                        case 'customer_site':
                            $pointsEnabledForPlatform = $settings->enable_points_for_customer_site === true;
                            break;
                        case 'kiosk':
                            $pointsEnabledForPlatform = $settings->enable_points_for_kiosk === true;
                            break;
                        default:
                            // Fallback: if source unknown, check old field
                            $pointsEnabledForPlatform = $settings->enable_for_pos ?? true;
                    }
                } else {
                    // Fallback to old field if new fields don't exist
                    switch ($orderSource) {
                        case 'pos':
                            $pointsEnabledForPlatform = $settings->enable_for_pos ?? true;
                            break;
                        case 'customer_site':
                            $pointsEnabledForPlatform = $settings->enable_for_customer_site ?? true;
                            break;
                        case 'kiosk':
                            $pointsEnabledForPlatform = $settings->enable_for_kiosk ?? true;
                            break;
                        default:
                            $pointsEnabledForPlatform = true; // Default to enabled if unknown
                    }
                }
                
                if ($pointsEnabledForPlatform) {
                    $this->loyaltyService->earnPoints($order);
                }
            }
            
            // Earn stamps only if:
            // 1. Stamps are enabled (enable_stamps = true)
            // 2. Loyalty type allows stamps (stamps or both)
            // 3. Stamps are enabled for the order's platform
            if ($settings->enable_stamps && in_array($loyaltyType, ['stamps', 'both'])) {
                $stampsEnabledForPlatform = false;
                
                if ($hasNewFields) {
                    // Use new platform-specific fields
                    switch ($orderSource) {
                        case 'pos':
                            $stampsEnabledForPlatform = $settings->enable_stamps_for_pos === true;
                            break;
                        case 'customer_site':
                            $stampsEnabledForPlatform = $settings->enable_stamps_for_customer_site === true;
                            break;
                        case 'kiosk':
                            $stampsEnabledForPlatform = $settings->enable_stamps_for_kiosk === true;
                            break;
                        default:
                            // Fallback: if source unknown, check old field
                            $stampsEnabledForPlatform = $settings->enable_for_pos ?? true;
                    }
                } else {
                    // Fallback to old field if new fields don't exist
                    switch ($orderSource) {
                        case 'pos':
                            $stampsEnabledForPlatform = $settings->enable_for_pos ?? true;
                            break;
                        case 'customer_site':
                            $stampsEnabledForPlatform = $settings->enable_for_customer_site ?? true;
                            break;
                        case 'kiosk':
                            $stampsEnabledForPlatform = $settings->enable_for_kiosk ?? true;
                            break;
                        default:
                            $stampsEnabledForPlatform = true; // Default to enabled if unknown
                    }
                }
                
                if ($stampsEnabledForPlatform) {
                    $result = $this->loyaltyService->earnStamps($order);
                    if (!$result) {
                        Log::info('Loyalty: No stamps earned for order', [
                            'order_id' => $order->id,
                            'order_number' => $order->order_number,
                            'customer_id' => $order->customer_id,
                            'stamp_discount_amount' => $order->stamp_discount_amount ?? 0,
                            'has_free_items' => $order->items()->where('is_free_item_from_stamp', true)->exists(),
                        ]);
                    }
                } else {
                    Log::info('Loyalty: Stamps not enabled for platform', [
                        'order_id' => $order->id,
                        'order_source' => $orderSource,
                        'enable_stamps_for_pos' => $settings->enable_stamps_for_pos ?? null,
                        'enable_stamps' => $settings->enable_stamps ?? null,
                        'loyalty_type' => $loyaltyType,
                    ]);
                }
            }

        } catch (\Exception $e) {
            Log::error('Loyalty: Failed to earn points/stamps on order completion', [
                'order_id' => $event->order->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
    
    /**
     * Detect the order source/platform (POS, Customer Site, or Kiosk)
     */
    protected function detectOrderSource($order): string
    {
        // Check placed_via field (most reliable indicator)
        if (isset($order->placed_via) && !empty($order->placed_via)) {
            $placedVia = strtolower($order->placed_via);
            
            // Map placed_via values to our platform identifiers
            if ($placedVia === 'pos') {
                return 'pos';
            } elseif (in_array($placedVia, ['shop', 'customer_site', 'website', 'web'])) {
                return 'customer_site';
            } elseif ($placedVia === 'kiosk') {
                return 'kiosk';
            }
        }
        
        // Check if order has kiosk_id (Kiosk module indicator)
        if (isset($order->kiosk_id) && $order->kiosk_id) {
            return 'kiosk';
        }
        
        // Check for table_id (POS indicator - dine-in orders)
        if (isset($order->table_id) && $order->table_id) {
            return 'pos';
        }
        
        // Check for delivery address (customer site indicator)
        if (isset($order->delivery_address) && $order->delivery_address) {
            return 'customer_site';
        }
        
        // Default to POS (most common for restaurant orders)
        return 'pos';
    }
}

