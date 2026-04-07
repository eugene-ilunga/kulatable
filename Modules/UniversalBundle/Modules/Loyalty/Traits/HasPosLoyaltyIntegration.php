<?php

namespace Modules\Loyalty\Traits;

use App\Models\Kot;
use App\Models\KotItem;
use App\Models\Order;
use App\Models\OrderCharge;
use App\Models\OrderItem;
use App\Models\OrderTax;
use App\Models\OrderType;
use App\Models\RestaurantCharge;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

trait HasPosLoyaltyIntegration
{
    public function isLoyaltyEnabled()
    {
        // Check if module is enabled
        if (!module_enabled('Loyalty')) {
            return false;
        }

        // Check if module is in restaurant's package
        if (function_exists('restaurant_modules')) {
            $restaurantModules = restaurant_modules();
            if (!in_array('Loyalty', $restaurantModules)) {
                return false;
            }
        }

        // Check platform-specific setting for POS
        try {
            if (module_enabled('Loyalty')) {
                $restaurantId = restaurant()->id ?? null;
                if ($restaurantId) {
                    $settings = \Modules\Loyalty\Entities\LoyaltySetting::getForRestaurant($restaurantId);
                    if (!$settings->enabled) {
                        return false;
                    }

                    // Check if new platform fields exist
                    $hasNewFields = !is_null($settings->enable_points_for_pos) && !is_null($settings->enable_stamps_for_pos);

                    if ($hasNewFields) {
                        // New fields exist - check if either points or stamps are enabled for POS
                        $pointsEnabled = $settings->enable_points && ($settings->enable_points_for_pos === true);
                        $stampsEnabled = $settings->enable_stamps && ($settings->enable_stamps_for_pos === true);
                        return $pointsEnabled || $stampsEnabled;
                    } else {
                        // Fallback to old field if new fields don't exist yet
                        return $settings->enable_for_pos ?? true;
                    }
                }
            }
        } catch (\Exception $e) {
            // Silently fail
        }

        return false;
    }

    /**
     * Check if points are enabled for POS platform
     */
    public function isPointsEnabledForPOS()
    {
        if (!$this->isLoyaltyEnabled()) {
            return false;
        }

        try {
            if (module_enabled('Loyalty')) {
                $restaurantId = restaurant()->id ?? null;
                if ($restaurantId) {
                    $settings = \Modules\Loyalty\Entities\LoyaltySetting::getForRestaurant($restaurantId);
                    $loyaltyType = $settings->loyalty_type ?? 'points';
                    $pointsEnabled = in_array($loyaltyType, ['points', 'both']) && ($settings->enable_points ?? true);

                    if (!$pointsEnabled) {
                        return false;
                    }

                    // Check if new platform field exists
                    $hasNewField = !is_null($settings->enable_points_for_pos);

                    if ($hasNewField) {
                        // Use loose comparison because DB returns 1/0, not true/false
                        return (bool) $settings->enable_points_for_pos;
                    } else {
                        // Fallback to old field
                        return (bool) ($settings->enable_for_pos ?? true);
                    }
                }
            }
        } catch (\Exception $e) {
            // Silently fail
        }

        return false;
    }

    /**
     * Check if stamps are enabled for POS platform
     */
    public function isStampsEnabledForPOS()
    {
        if (!$this->isLoyaltyEnabled()) {
            return false;
        }

        try {
            if (module_enabled('Loyalty')) {
                $restaurantId = restaurant()->id ?? null;
                if ($restaurantId) {
                    $settings = \Modules\Loyalty\Entities\LoyaltySetting::getForRestaurant($restaurantId);
                    $loyaltyType = $settings->loyalty_type ?? 'points';
                    $stampsEnabled = in_array($loyaltyType, ['stamps', 'both']) && ($settings->enable_stamps ?? true);

                    if (!$stampsEnabled) {
                        return false;
                    }

                    // Check if new platform field exists
                    $hasNewField = !is_null($settings->enable_stamps_for_pos);

                    if ($hasNewField) {
                        // Use loose comparison because DB returns 1/0, not true/false
                        return (bool) $settings->enable_stamps_for_pos;
                    } else {
                        // Fallback to old field
                        return (bool) ($settings->enable_for_pos ?? true);
                    }
                }
            }
        } catch (\Exception $e) {
            // Silently fail
        }

        return false;
    }

    /**
     * Reset loyalty redemption (stub method if trait doesn't exist)
     */
    public function resetLoyaltyRedemption()
    {
        if (!module_enabled('Loyalty')) {
            return;
        }

        $traits = class_uses_recursive(static::class);
        
        if (in_array(\Modules\Loyalty\Traits\HasLoyaltyIntegration::class, $traits)) {
            // Trait exists and is used, call parent method
            return;
        }

        // Stub: reset loyalty properties to defaults
        $this->loyaltyPointsRedeemed = 0;
        $this->loyaltyDiscountAmount = 0;
        $this->availableLoyaltyPoints = 0;
        $this->pointsToRedeem = 0;
        $this->maxRedeemablePoints = 0;
        $this->minRedeemPoints = 0;
        $this->showLoyaltyRedemptionModal = false;
    }

    /**
     * Check loyalty points on customer select (stub method if trait doesn't exist)
     */
    public function checkLoyaltyPointsOnCustomerSelect()
    {
        if (!$this->isLoyaltyEnabled() || !$this->customerId) {
            return;
        }

        // If module exists, implement the logic directly (since we can't use trait conditionally)
        if (module_enabled('Loyalty')) {
            try {
                $loyaltyService = app(\Modules\Loyalty\Services\LoyaltyService::class);
                $restaurantId = restaurant()->id;
                $this->availableLoyaltyPoints = $loyaltyService->getAvailablePoints($restaurantId, $this->customerId);

                // Load loyalty settings
                if (module_enabled('Loyalty')) {
                    $settings = \Modules\Loyalty\Entities\LoyaltySetting::getForRestaurant($restaurantId);

                    // Check if points are enabled based on loyalty_type
                    $loyaltyType = $settings->loyalty_type ?? 'points';
                    $pointsEnabled = in_array($loyaltyType, ['points', 'both']) && ($settings->enable_points ?? true);

                    // Check if points are enabled for POS platform
                    $pointsEnabledForPOS = $this->isPointsEnabledForPOS();

                    if ($settings && $settings->isEnabled() && $pointsEnabled && $pointsEnabledForPOS && $this->availableLoyaltyPoints > 0) {
                        // Calculate subtotal
                        $this->subTotal = $this->orderItemAmount ? array_sum($this->orderItemAmount) : 0;
                        $valuePerPoint = $settings->value_per_point ?? 1;
                        $this->minRedeemPoints = $settings->min_redeem_points ?? 0;

                        // Calculate loyalty points value (total value of all available points)
                        $this->loyaltyPointsValue = $this->availableLoyaltyPoints * $valuePerPoint;

                        // Calculate max discount TODAY (percentage of subtotal)
                        // This is the maximum discount allowed today based on max_discount_percent setting
                        $subtotal = $this->subTotal ?? 0;
                        $maxDiscountToday = 0;
                        if ($subtotal > 0) {
                            $maxDiscountToday = $subtotal * ($settings->max_discount_percent / 100);
                        }
                        $this->maxLoyaltyDiscount = $maxDiscountToday; // Store for display

                        // Calculate maximum points based on available points (multiple of min_redeem_points)
                        $maxPointsFromAvailable = 0;
                        if ($this->minRedeemPoints > 0 && $this->availableLoyaltyPoints >= $this->minRedeemPoints) {
                            $maxPointsFromAvailable = floor($this->availableLoyaltyPoints / $this->minRedeemPoints) * $this->minRedeemPoints;
                        }

                        // Calculate maximum points based on max discount TODAY (multiple of min_redeem_points)
                        // This ensures "Use Max" button respects the Maximum Discount (%) setting
                        $maxPointsFromDiscount = 0;
                        if ($maxDiscountToday > 0 && $this->minRedeemPoints > 0) {
                            $maxPointsFromDiscountValue = floor($maxDiscountToday / $valuePerPoint);
                            if ($maxPointsFromDiscountValue >= $this->minRedeemPoints) {
                                $maxPointsFromDiscount = floor($maxPointsFromDiscountValue / $this->minRedeemPoints) * $this->minRedeemPoints;
                            }
                        }

                        // Maximum redeemable points is the minimum of both constraints
                        if ($maxPointsFromDiscount > 0 && $maxPointsFromAvailable > 0) {
                            $this->maxRedeemablePoints = min($maxPointsFromAvailable, $maxPointsFromDiscount);
                        } elseif ($maxPointsFromAvailable > 0) {
                            $this->maxRedeemablePoints = $maxPointsFromAvailable;
                        } elseif ($maxPointsFromDiscount > 0) {
                            $this->maxRedeemablePoints = $maxPointsFromDiscount;
                        } else {
                            $this->maxRedeemablePoints = 0;
                        }

                        // Auto-open modal if customer has points AND there are items in cart AND points are enabled for POS
                        if ($this->isPointsEnabledForPOS() && $this->availableLoyaltyPoints > 0 && !empty($this->orderItemList) && $this->subTotal > 0) {
                            $this->showLoyaltyRedemptionModal = true;
                         
                        } else {
                          
                        }
                    } else {
                       
                    }
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Error checking loyalty points: ' . $e->getMessage(), [
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }
        // If module doesn't exist, do nothing
    }

    /**
     * Update loyalty values (stub method if trait doesn't exist)
     */
    public function updateLoyaltyValues()
    {
        if (!$this->isLoyaltyEnabled() || !$this->customerId) {
            return;
        }

        // If module exists, implement the logic directly
        if (module_enabled('Loyalty')) {
            try {
                $loyaltyService = app(\Modules\Loyalty\Services\LoyaltyService::class);
                $restaurantId = restaurant()->id;
                $this->availableLoyaltyPoints = $loyaltyService->getAvailablePoints($restaurantId, $this->customerId);

                // Load loyalty settings
                if (module_enabled('Loyalty')) {
                    $settings = \Modules\Loyalty\Entities\LoyaltySetting::getForRestaurant($restaurantId);

                    // Check if points are enabled based on loyalty_type
                    $loyaltyType = $settings->loyalty_type ?? 'points';
                    $pointsEnabled = in_array($loyaltyType, ['points', 'both']) && ($settings->enable_points ?? true);

                    if ($settings && $settings->isEnabled() && $pointsEnabled) {
                        $valuePerPoint = $settings->value_per_point ?? 1;
                        $this->minRedeemPoints = $settings->min_redeem_points ?? 0;

                        // Calculate loyalty points value (total value of all available points)
                        $this->loyaltyPointsValue = $this->availableLoyaltyPoints * $valuePerPoint;

                        // Calculate max discount TODAY (percentage of subtotal)
                        // This is the maximum discount allowed today based on max_discount_percent setting
                        $subtotal = $this->subTotal ?? 0;
                        $maxDiscountToday = 0;
                        if ($subtotal > 0) {
                            $maxDiscountToday = $subtotal * ($settings->max_discount_percent / 100);
                        }
                        $this->maxLoyaltyDiscount = $maxDiscountToday; // Store for display

                        // Calculate maximum points based on available points (multiple of min_redeem_points)
                        $maxPointsFromAvailable = 0;
                        if ($this->minRedeemPoints > 0 && $this->availableLoyaltyPoints >= $this->minRedeemPoints) {
                            $maxPointsFromAvailable = floor($this->availableLoyaltyPoints / $this->minRedeemPoints) * $this->minRedeemPoints;
                        }

                        // Calculate maximum points based on max discount TODAY (multiple of min_redeem_points)
                        // This ensures "Use Max" button respects the Maximum Discount (%) setting
                        $maxPointsFromDiscount = 0;
                        if ($maxDiscountToday > 0 && $this->minRedeemPoints > 0) {
                            $maxPointsFromDiscountValue = floor($maxDiscountToday / $valuePerPoint);
                            if ($maxPointsFromDiscountValue >= $this->minRedeemPoints) {
                                $maxPointsFromDiscount = floor($maxPointsFromDiscountValue / $this->minRedeemPoints) * $this->minRedeemPoints;
                            }
                        }

                        // Maximum redeemable points is the minimum of both constraints
                        if ($maxPointsFromDiscount > 0 && $maxPointsFromAvailable > 0) {
                            $this->maxRedeemablePoints = min($maxPointsFromAvailable, $maxPointsFromDiscount);
                        } elseif ($maxPointsFromAvailable > 0) {
                            $this->maxRedeemablePoints = $maxPointsFromAvailable;
                        } elseif ($maxPointsFromDiscount > 0) {
                            $this->maxRedeemablePoints = $maxPointsFromDiscount;
                        } else {
                            $this->maxRedeemablePoints = 0;
                        }
                    }
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Error updating loyalty values: ' . $e->getMessage());
            }
        }
        // If module doesn't exist, do nothing
    }

    /**
     * Load loyalty data for order (stub method if trait doesn't exist)
     */
    public function loadLoyaltyDataForOrder($order, $restaurantId, $customerId, $subTotal)
    {
        if (module_enabled('Loyalty')) {
            $traits = class_uses_recursive(static::class);
            if (in_array(\Modules\Loyalty\Traits\HasLoyaltyIntegration::class, $traits)) {
                // Trait exists and is used, it will handle this
                return;
            }
        }
        // Stub: do nothing if module doesn't exist
    }

    /**
     * Open loyalty redemption modal and load loyalty values
     */
    public function openLoyaltyRedemptionModal()
    {
        if ($this->isLoyaltyEnabled() && $this->customerId) {
            // Check if there are items in cart
            if (empty($this->orderItemList) || $this->subTotal <= 0) {
                $this->alert('info', __('Please add items to cart before redeeming points.'), [
                    'toast' => true,
                    'position' => 'top-end',
                ]);
                return;
            }

            // Load loyalty points and values
            try {
                $loyaltyService = app(\Modules\Loyalty\Services\LoyaltyService::class);
                $restaurantId = restaurant()->id;

                // Check if points are enabled for POS platform
                if (!$this->isPointsEnabledForPOS()) {
                    $this->alert('info', __('loyalty::app.pointsNotEnabledForPOS'), [
                        'toast' => true,
                        'position' => 'top-end',
                    ]);
                    return;
                }

                // Get available points
                $this->availableLoyaltyPoints = $loyaltyService->getAvailablePoints($restaurantId, $this->customerId);

                if ($this->availableLoyaltyPoints > 0) {
                    // Update loyalty values
                    $this->updateLoyaltyValues();

                    // Set default points to redeem
                    $this->pointsToRedeem = $this->minRedeemPoints > 0 ? $this->minRedeemPoints : ($this->maxRedeemablePoints > 0 ? $this->maxRedeemablePoints : 0);

                    // Open modal
                    $this->showLoyaltyRedemptionModal = true;
                } else {
                    $this->alert('info', __('loyalty::app.noPointsAvailable'), [
                        'toast' => true,
                        'position' => 'top-end',
                    ]);
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Failed to open loyalty redemption modal: ' . $e->getMessage());
                $this->alert('error', __('loyalty::app.failedToLoadPoints'), [
                    'toast' => true,
                    'position' => 'top-end',
                ]);
            }
        }
    }

    /**
     * Edit existing loyalty redemption (for POS - when order already has redemption)
     */
    public function editLoyaltyRedemption()
    {
        if (!$this->isLoyaltyEnabled() || !$this->customerId) {
            $errorMsg = function_exists('__') && function_exists('module_enabled') && module_enabled('Loyalty')
                ? __('loyalty::app.loyaltyProgramNotEnabled')
                : __('loyalty::app.loyaltyProgramNotEnabled');
            $this->alert('error', $errorMsg, [
                'toast' => true,
                'position' => 'top-end',
            ]);
            return;
        }

        // Load loyalty points and values
        try {
            $loyaltyService = app(\Modules\Loyalty\Services\LoyaltyService::class);
            $restaurantId = restaurant()->id;

            // Get available points (including any that were already redeemed, since we're editing)
            $this->availableLoyaltyPoints = $loyaltyService->getAvailablePoints($restaurantId, $this->customerId);

            // If there's an existing order with redemption, add those points back to available
            if ($this->orderID && $this->orderDetail) {
                $existingRedeemed = $this->orderDetail->loyalty_points_redeemed ?? 0;
                if ($existingRedeemed > 0) {
                    // Add back the redeemed points to available (since we're editing, they'll be available again)
                    $this->availableLoyaltyPoints += $existingRedeemed;
                }
            }

            // Update loyalty values (max redeemable, etc.)
            $this->updateLoyaltyValues();

            // Pre-fill with current redemption if exists
            if ($this->loyaltyPointsRedeemed > 0) {
                $this->pointsToRedeem = $this->loyaltyPointsRedeemed;
            } else {
                // Set default points to redeem
                $this->pointsToRedeem = $this->minRedeemPoints > 0 ? $this->minRedeemPoints : ($this->maxRedeemablePoints > 0 ? $this->maxRedeemablePoints : 0);
            }

            // Recalculate discount preview with current points
            $this->updatedPointsToRedeem();

            // Open modal
            $this->showLoyaltyRedemptionModal = true;
        } catch (\Exception $e) {
            $errorMsg = function_exists('__') && function_exists('module_enabled') && module_enabled('Loyalty')
                ?? __('loyalty::app.failedToLoadPoints');
            $this->alert('error', $errorMsg, [
                'toast' => true,
                'position' => 'top-end',
            ]);
        }
    }

    /**
     * Redeem loyalty points (for POS - before order is created)
     */
    public function redeemLoyaltyPoints($points = null)
    {
        if (!$this->isLoyaltyEnabled() || !$this->customerId) {
            $errorMsg = function_exists('__') && function_exists('module_enabled') && module_enabled('Loyalty')
                ? __('loyalty::app.loyaltyProgramNotEnabled')
                : __('loyalty::app.loyaltyProgramNotEnabled');
            $this->alert('error', $errorMsg, [
                'toast' => true,
                'position' => 'top-end',
            ]);
            return;
        }

        // Check if points are enabled for POS platform
        if (!$this->isPointsEnabledForPOS()) {
            $this->alert('error', __('loyalty::app.pointsNotEnabledForPOS'), [
                'toast' => true,
                'position' => 'top-end',
            ]);
            return;
        }

        // If points parameter is provided (e.g., from Use Max button), use it and update pointsToRedeem
        if ($points !== null && $points > 0) {
            $this->pointsToRedeem = (int) $points;
            $pointsToRedeem = (int) $points;
        } else {
            // Use pointsToRedeem from input, or default to min/max if not set
            $pointsToRedeem = $this->pointsToRedeem ?? 0;

            // If pointsToRedeem is 0, use min redeem points or max redeemable
            if ($pointsToRedeem <= 0) {
                $pointsToRedeem = $this->minRedeemPoints > 0 ? $this->minRedeemPoints : ($this->maxRedeemablePoints > 0 ? $this->maxRedeemablePoints : 0);
            }
        }

        if ($pointsToRedeem <= 0) {
            $errorMsg = function_exists('__') && function_exists('module_enabled') && module_enabled('Loyalty')
                ? __('loyalty::app.invalidPointsAmount')
                : __('loyalty::app.invalidPointsAmount');
            $this->alert('error', $errorMsg, [
                'toast' => true,
                'position' => 'top-end',
            ]);
            return;
        }

        // Validate points
        if ($pointsToRedeem > $this->availableLoyaltyPoints) {
            $errorMsg = function_exists('__') && function_exists('module_enabled') && module_enabled('Loyalty')
                ? __('loyalty::app.insufficientLoyaltyPointsAvailable')
                : __('loyalty::app.insufficientLoyaltyPointsAvailable');
            $this->alert('error', $errorMsg, [
                'toast' => true,
                'position' => 'top-end',
            ]);
            return;
        }

        if ($this->minRedeemPoints > 0 && $pointsToRedeem < $this->minRedeemPoints) {
            $errorMsg = function_exists('__') && function_exists('module_enabled') && module_enabled('Loyalty')
                ? __('loyalty::app.minPointsRequired', ['min_points' => $this->minRedeemPoints])
                : __('loyalty::app.minPointsRequired', ['min_points' => $this->minRedeemPoints]);
            $this->alert('error', $errorMsg, [
                'toast' => true,
                'position' => 'top-end',
            ]);
            return;
        }

        if ($pointsToRedeem > $this->maxRedeemablePoints && $this->maxRedeemablePoints > 0) {
            $pointsToRedeem = $this->maxRedeemablePoints;
            $this->pointsToRedeem = $pointsToRedeem;
        }

        // Calculate discount amount
        if (!module_enabled('Loyalty')) {
            $errorMsg = function_exists('__') && function_exists('module_enabled') && module_enabled('Loyalty')
                ? __('loyalty::app.loyaltyModuleNotAvailable')
                : 'Loyalty module is not available';
            $this->alert('error', $errorMsg, [
                'toast' => true,
                'position' => 'top-end',
            ]);
            return;
        }

        try {
            $settings = \Modules\Loyalty\Entities\LoyaltySetting::getForRestaurant(restaurant()->id);
            if (!$settings) {
                $errorMsg = function_exists('__') && function_exists('module_enabled') && module_enabled('Loyalty')
                    ? __('loyalty::app.loyaltySettingsNotFoundConfigure')
                    : 'Loyalty settings not found. Please configure loyalty settings first.';
                $this->alert('error', $errorMsg, [
                    'toast' => true,
                    'position' => 'top-end',
                ]);
                return;
            }

            if (!$settings->isEnabled()) {
                $errorMsg = function_exists('__') && function_exists('module_enabled') && module_enabled('Loyalty')
                    ? __('loyalty::app.loyaltyProgramNotEnabledForRestaurant')
                    : 'Loyalty program is not enabled for this restaurant.';
                $this->alert('error', $errorMsg, [
                    'toast' => true,
                    'position' => 'top-end',
                ]);
                return;
            }

            $valuePerPoint = $settings->value_per_point ?? 1;
            $basePointsDiscount = $pointsToRedeem * $valuePerPoint;

            // Apply tier redemption multiplier if customer has a tier
            $tierMultiplier = 1.00;
            if ($this->customerId && module_enabled('Loyalty')) {
                try {
                    $loyaltyService = app(\Modules\Loyalty\Services\LoyaltyService::class);
                    $account = $loyaltyService->getOrCreateAccount(restaurant()->id, $this->customerId);
                    if ($account && $account->tier_id) {
                        $tier = \Modules\Loyalty\Entities\LoyaltyTier::find($account->tier_id);
                        if ($tier && $tier->redemption_multiplier > 0) {
                            $tierMultiplier = $tier->redemption_multiplier;
                        }
                    }
                } catch (\Exception $e) {
                    // If tier check fails, use default multiplier of 1.00
                    \Illuminate\Support\Facades\Log::warning('Error checking tier for points redemption: ' . $e->getMessage());
                }
            }

            $pointsDiscount = $basePointsDiscount * $tierMultiplier;

            // Calculate max discount based on subtotal
            // Ensure subtotal is calculated
            if ($this->subTotal <= 0) {
                $this->subTotal = $this->orderItemAmount ? array_sum($this->orderItemAmount) : 0;
            }

            $maxDiscountPercent = $settings->max_discount_percent ?? 0;
            $maxDiscountAmount = ($this->subTotal * $maxDiscountPercent) / 100;

            // Use the smaller of points discount or max discount
            $this->loyaltyDiscountAmount = min($pointsDiscount, $maxDiscountAmount);
            $this->loyaltyPointsRedeemed = $pointsToRedeem;

            // Recalculate totals with loyalty discount
            $this->calculateTotal();

            // Close modal
            $this->showLoyaltyRedemptionModal = false;

            $successMsg = function_exists('__') && function_exists('module_enabled') && module_enabled('Loyalty')
                ? __('loyalty::app.pointsRedeemedForDiscount', [
                    'points' => $pointsToRedeem,
                    'discount' => currency_format($this->loyaltyDiscountAmount, restaurant()->currency_id)
                ])
                : __('loyalty::app.pointsRedeemedForDiscount', [
                    'points' => $pointsToRedeem,
                    'discount' => currency_format($this->loyaltyDiscountAmount, restaurant()->currency_id)
                ]);

            $this->alert('success', $successMsg, [
                'toast' => true,
                'position' => 'top-end',
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error redeeming loyalty points: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'points' => $pointsToRedeem,
                'customer_id' => $this->customerId,
                'subtotal' => $this->subTotal,
            ]);

            // Show user-friendly error message
            $errorMsg = function_exists('__') && function_exists('module_enabled') && module_enabled('Loyalty')
                ? __('loyalty::app.failedToRedeemPoints')
                : 'Failed to redeem loyalty points.';

            if (config('app.debug')) {
                $errorMsg .= ': ' . $e->getMessage();
            }

            $this->alert('error', $errorMsg, [
                'toast' => true,
                'position' => 'top-end',
            ]);
        }
    }

    /**
     * Updated points to redeem (when user changes input)
     */
    public function updatedPointsToRedeem()
    {
        if ($this->pointsToRedeem > $this->maxRedeemablePoints && $this->maxRedeemablePoints > 0) {
            $this->pointsToRedeem = $this->maxRedeemablePoints;
        }

        // Recalculate discount preview
        if ($this->pointsToRedeem > 0 && module_enabled('Loyalty')) {
            try {
                $settings = \Modules\Loyalty\Entities\LoyaltySetting::getForRestaurant(restaurant()->id);
                if ($settings) {
                    $valuePerPoint = $settings->value_per_point ?? 1;
                    $pointsDiscount = $this->pointsToRedeem * $valuePerPoint;

                    // Calculate max discount based on subtotal
                    $maxDiscountPercent = $settings->max_discount_percent ?? 0;
                    $maxDiscountAmount = ($this->subTotal * $maxDiscountPercent) / 100;

                    // Use the smaller of points discount or max discount
                    $this->loyaltyDiscountAmount = min($pointsDiscount, $maxDiscountAmount);
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Error calculating loyalty discount preview: ' . $e->getMessage());
            }
        }
    }

    /**
     * Skip loyalty redemption (close modal without redeeming)
     */
    public function skipLoyaltyRedemption()
    {
        $this->showLoyaltyRedemptionModal = false;
        // Reset points to redeem but keep available points loaded
        $this->pointsToRedeem = 0;
    }

    /**
     * Get loyalty order data for saving to database (stub method if trait doesn't exist)
     * Returns array with loyalty_points_redeemed and loyalty_discount_amount
     */
    public function getLoyaltyOrderData()
    {
        if (!$this->isLoyaltyEnabled()) {
            return [
                'loyalty_points_redeemed' => $this->loyaltyPointsRedeemed ?? 0,
                'loyalty_discount_amount' => $this->loyaltyDiscountAmount ?? 0,
            ];
        }

        if (module_enabled('Loyalty')) {
            $traits = class_uses_recursive(static::class);
            if (in_array(\Modules\Loyalty\Traits\HasLoyaltyIntegration::class, $traits)) {
                // Trait exists and is used, try to call trait method if it exists
                if (method_exists($this, 'traitGetLoyaltyOrderData')) {
                    return $this->traitGetLoyaltyOrderData();
                }
            }
        }
        // Stub: return loyalty data from component properties
        return [
            'loyalty_points_redeemed' => $this->loyaltyPointsRedeemed ?? 0,
            'loyalty_discount_amount' => $this->loyaltyDiscountAmount ?? 0,
        ];
    }

    /**
     * Recalculate order total after stamp redemption (for POS)
     * This preserves loyalty values when stamps are redeemed
     */
    protected function recalculateOrderTotalAfterStampRedemption($order)
    {
        try {
            // Reload order with all relationships to get accurate item amounts
            $order->refresh();
            $order->load(['items', 'taxes.tax', 'kot.items']);

            // Calculate subtotal - for KOT orders, use KOT items; otherwise use order items
            $correctSubTotal = 0.0;
            if ($order->status === 'kot' && $order->kot && $order->kot->count() > 0) {
                // For KOT orders, calculate from KOT items
                foreach ($order->kot as $kot) {
                    foreach ($kot->items as $kotItem) {
                        $menuItem = $kotItem->menuItem;
                        $variation = $kotItem->menuItemVariation;
                        $itemPrice = $variation ? ($variation->price ?? 0) : ($menuItem->price ?? 0);

                        // Add modifier prices
                        $modifierPrice = 0;
                        if ($kotItem->modifierOptions && $kotItem->modifierOptions->count() > 0) {
                            $modifierPrice = $kotItem->modifierOptions->sum('price');
                        }

                        $correctSubTotal += ($itemPrice + $modifierPrice) * $kotItem->quantity;
                    }
                }
            } else {
                // For non-KOT orders, calculate from order items (free items have amount=0)
                $correctSubTotal = (float)($order->items->sum('amount') ?? 0);
            }

            $discountedSubTotal = (float)$correctSubTotal;

            // Apply regular discount if any
            $discountedSubTotal -= (float)($order->discount_amount ?? 0);

            // Apply loyalty discount BEFORE tax calculation
            $discountedSubTotal -= (float)($order->loyalty_discount_amount ?? 0);

            // Step 1: Calculate service charges on discounted subtotal (after all discounts)
            $serviceTotal = 0;
            if ($order->charges && $order->charges->count() > 0) {
                foreach ($order->charges as $chargeRelation) {
                    $charge = $chargeRelation->charge;
                    if ($charge) {
                        $serviceTotal += (float)$charge->getAmount($discountedSubTotal);
                    }
                }
            }

            // Step 2: Calculate tax_base based on setting
            // Tax base = (subtotal - discounts) + service charges (if enabled)
            $includeChargesInTaxBase = $this->restaurant->include_charges_in_tax_base ?? true;
            $taxBase = $includeChargesInTaxBase ? ($discountedSubTotal + $serviceTotal) : $discountedSubTotal;
            $taxBase = max(0, (float)$taxBase);

            // Step 3: Calculate taxes on tax_base (AFTER all discounts and considering service charges)
            $correctTaxAmount = 0.0;
            $taxMode = $order->tax_mode ?? 'order';

            if ($taxMode === 'order') {
                // Order-level taxes - calculate on tax_base
                if ($order->taxes && $order->taxes->count() > 0) {
                    foreach ($order->taxes as $orderTax) {
                        $tax = $orderTax->tax;
                        if ($tax && isset($tax->tax_percent)) {
                            $taxPercent = (float)$tax->tax_percent;
                            $taxAmount = ($taxPercent / 100.0) * (float)$taxBase;
                            $correctTaxAmount += $taxAmount;
                        }
                    }
                }
                $correctTaxAmount = round($correctTaxAmount, 2);
            } else {
                // Item-level taxes - sum from order items
                $correctTaxAmount = (float)($order->items->sum('tax_amount') ?? 0);
            }

            // Step 4: Start total calculation from discounted subtotal
            $correctTotal = max(0, (float)$discountedSubTotal);

            // Step 5: Add service charges to total
            $correctTotal += $serviceTotal;

            // Step 6: Add taxes to total
            if ($taxMode === 'order') {
                // Order-level taxes are always exclusive, so add them
                $correctTotal += (float)$correctTaxAmount;
            } else {
                // Item-level taxes
                $isInclusive = ($this->restaurant->tax_inclusive ?? false);
                if (!$isInclusive && $correctTaxAmount > 0) {
                    $correctTotal += (float)$correctTaxAmount;
                }
            }

            // Round final values
            $correctSubTotal = round($correctSubTotal, 2);
            $correctTotal = round($correctTotal, 2);
            $correctTaxAmount = round($correctTaxAmount, 2);

            // Preserve loyalty values when updating order
            $updateData = [
                'sub_total' => $correctSubTotal,
                'total' => $correctTotal,
                'total_tax_amount' => $correctTaxAmount,
            ];

            // CRITICAL: Preserve loyalty fields if they exist (in case points were also redeemed)
            if ($order->loyalty_points_redeemed > 0) {
                $updateData['loyalty_points_redeemed'] = $order->loyalty_points_redeemed;
            }
            if ($order->loyalty_discount_amount > 0) {
                $updateData['loyalty_discount_amount'] = $order->loyalty_discount_amount;
            }

            // Update order with all calculated values (preserving loyalty values)
            $order->update($updateData);

            // Refresh order
            $order->refresh();
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to recalculate order total after stamp redemption in POS: ' . $e->getMessage());
        }
    }

    /**
     * Validate and adjust loyalty points when order total changes after points are already redeemed
     */
    public function validateAndAdjustLoyaltyPoints()
    {
        if (!$this->isLoyaltyEnabled() || !$this->customerId || !isset($this->loyaltyPointsRedeemed) || $this->loyaltyPointsRedeemed <= 0) {
            return;
        }

        // Get current loyalty settings (only if module exists)
        if (!module_enabled('Loyalty')) {
            return;
        }

        $restaurantId = restaurant()->id;
        $settings = \Modules\Loyalty\Entities\LoyaltySetting::where('restaurant_id', $restaurantId)->first();

        if (!$settings) {
            return;
        }

        // Calculate maximum allowed discount based on current subtotal
        $maxDiscountPercent = $settings->max_discount_percent ?? 0;
        $maxDiscountAmount = ($this->subTotal * $maxDiscountPercent) / 100;

        // Calculate maximum redeemable points based on max discount
        $valuePerPoint = $settings->value_per_point ?? 1;
        $maxRedeemablePoints = floor($maxDiscountAmount / $valuePerPoint);

        // Check if currently redeemed points exceed the new maximum
        if ($this->loyaltyPointsRedeemed > $maxRedeemablePoints) {
            // Adjust redeemed points to the new maximum
            $previousPoints = $this->loyaltyPointsRedeemed;
            $this->loyaltyPointsRedeemed = $maxRedeemablePoints;

            // Recalculate discount amount
            $this->loyaltyDiscountAmount = $this->loyaltyPointsRedeemed * $valuePerPoint;

            // Update max redeemable points for display
            $this->maxRedeemablePoints = $maxRedeemablePoints;

            // Show notification to user about the adjustment
            if ($maxRedeemablePoints > 0) {
                $this->alert('warning', __('loyalty::app.pointsAdjustedDueToOrderChange', [
                    'previous' => $previousPoints,
                    'current' => $this->loyaltyPointsRedeemed,
                    'reason' => __('loyalty::app.maxDiscountLimitReached')
                ]), [
                    'toast' => true,
                    'position' => 'top-end',
                    'timer' => 5000
                ]);
            } else {
                // No points can be redeemed with current order total
                $this->loyaltyPointsRedeemed = 0;
                $this->loyaltyDiscountAmount = 0;
                $this->maxRedeemablePoints = 0;

                $this->alert('warning', __('loyalty::app.pointsRemovedDueToOrderChange', [
                    'previous' => $previousPoints,
                    'reason' => __('loyalty::app.orderTotalTooLow')
                ]), [
                    'toast' => true,
                    'position' => 'top-end',
                    'timer' => 5000
                ]);
            }
        } else {
            // Points are still valid, just update max redeemable for display
            $this->maxRedeemablePoints = $maxRedeemablePoints;
        }
    }

    /**
     * Load customer stamps when customer is selected
     */
    public function loadCustomerStamps()
    {
        if (!$this->isLoyaltyEnabled() || !$this->customerId) {
            return;
        }

        if (!module_enabled('Loyalty')) {
            return;
        }

        try {
            $loyaltyService = app(\Modules\Loyalty\Services\LoyaltyService::class);
            $restaurantId = restaurant()->id;

            // Get customer stamps with rules
            $this->customerStamps = $loyaltyService->getCustomerStamps($restaurantId, $this->customerId);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to load customer stamps: ' . $e->getMessage());
        }
    }

    /**
     * Open stamp redemption modal
     */
    public function openStampRedemptionModal()
    {
        if (!$this->isLoyaltyEnabled() || !$this->customerId) {
            $this->alert('error', __('loyalty::app.loyaltyProgramNotEnabled'), [
                'toast' => true,
                'position' => 'top-end',
            ]);
            return;
        }

        // Check if stamps are enabled based on loyalty_type
        try {
            $restaurantId = restaurant()->id;
            $settings = \Modules\Loyalty\Entities\LoyaltySetting::getForRestaurant($restaurantId);
            $loyaltyType = $settings->loyalty_type ?? 'points';
            $stampsEnabled = in_array($loyaltyType, ['stamps', 'both']) && ($settings->enable_stamps ?? true);

            if (!$stampsEnabled) {
                $this->alert('info', __('loyalty::app.stampsNotEnabledForThisLoyaltyType'), [
                    'toast' => true,
                    'position' => 'top-end',
                ]);
                return;
            }
        } catch (\Exception $e) {
            $this->alert('error', __('loyalty::app.loyaltyProgramNotEnabled'), [
                'toast' => true,
                'position' => 'top-end',
            ]);
            return;
        }

        // Load customer stamps if not loaded
        if (empty($this->customerStamps)) {
            $this->loadCustomerStamps();
        }

        // Filter stamps that can be redeemed
        $redeemableStamps = collect($this->customerStamps)->filter(function ($stampData) {
            return $stampData['can_redeem'] ?? false;
        });

        if ($redeemableStamps->isEmpty()) {
            $this->alert('info', __('loyalty::app.noStampsAvailable'), [
                'toast' => true,
                'position' => 'top-end',
            ]);
            return;
        }

        // Open modal
        $this->showStampRedemptionModal = true;
    }

    /**
     * Redeem stamps for current order
     */
    public function redeemStamps($stampRuleId = null)
    {
        if (!$this->isLoyaltyEnabled() || !$this->customerId) {
            $this->alert('error', __('loyalty::app.loyaltyProgramNotEnabled'), [
                'toast' => true,
                'position' => 'top-end',
            ]);
            return;
        }

        // Check if stamps are enabled for POS platform
        if (!$this->isStampsEnabledForPOS()) {
            $this->alert('error', __('loyalty::app.stampsNotEnabledForPOS'), [
                'toast' => true,
                'position' => 'top-end',
            ]);
            return;
        }

        $stampRuleId = $stampRuleId ?? $this->selectedStampRuleId;

        if (!$stampRuleId) {
            $this->alert('error', __('loyalty::app.pleaseSelectStampRule'), [
                'toast' => true,
                'position' => 'top-end',
            ]);
            return;
        }

        // Check if order exists (for existing orders) or if we're creating new order
        if ($this->orderID) {
            // For existing orders, redeem stamps directly
            try {
                $order = Order::find($this->orderID);
                if (!$order) {
                    $this->alert('error', __('modules.order.orderNotFound'), [
                        'toast' => true,
                        'position' => 'top-end',
                    ]);
                    return;
                }

                // Redeem stamps for all eligible items on this order
                $this->redeemStampsForAllEligibleItems($order, $stampRuleId);

                // Reload order to get updated items/discounts
                $order->refresh();
                $order->load('items');

                // After multi-redemption, we don't rely on a single result array
                // We assume at least one redemption was applied if there are eligible items
                if (true) {
                    // Reload order to get updated items/discounts
                    $order->refresh();
                    $order->load('items');

                    // Update component properties
                    $this->stampDiscountAmount = $order->stamp_discount_amount ?? 0;

                    // Recalculate totals
                    $this->calculateTotal();

                    // Close modal
                    $this->showStampRedemptionModal = false;
                    $this->selectedStampRuleId = null;

                    // Reload customer stamps
                    $this->loadCustomerStamps();

                    $this->alert('success', $result['message'], [
                        'toast' => true,
                        'position' => 'top-end',
                    ]);
                } else {
                    $this->alert('error', $result['message'], [
                        'toast' => true,
                        'position' => 'top-end',
                    ]);
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Failed to redeem stamps: ' . $e->getMessage());
                $this->alert('error', __('loyalty::app.failedToRedeemStamps', ['error' => $e->getMessage()]), [
                    'toast' => true,
                    'position' => 'top-end',
                ]);
            }
        } else {
            // For new orders (not yet created), store stamp rule ID to redeem when order is created
            $this->selectedStampRuleId = $stampRuleId;

            // Find the stamp data to show preview
            $stampData = collect($this->customerStamps)->firstWhere('rule.id', $stampRuleId);
            if ($stampData) {
                $stampRule = $stampData['rule'];
                $baseRewardValue = $stampRule->calculateRewardValue($this->subTotal);

                // Apply tier redemption multiplier for discount rewards
                $tierMultiplier = 1.00;
                if (
                    in_array($stampRule->reward_type, ['discount_percent', 'discount_amount']) &&
                    $this->customerId &&
                    module_enabled('Loyalty')
                ) {
                    try {
                        $loyaltyService = app(\Modules\Loyalty\Services\LoyaltyService::class);
                        $account = $loyaltyService->getOrCreateAccount(restaurant()->id, $this->customerId);
                        if ($account && $account->tier_id) {
                            $tier = \Modules\Loyalty\Entities\LoyaltyTier::find($account->tier_id);
                            if ($tier && $tier->redemption_multiplier > 0) {
                                $tierMultiplier = $tier->redemption_multiplier;
                            }
                        }
                    } catch (\Exception $e) {
                        // If tier check fails, use default multiplier of 1.00
                        \Illuminate\Support\Facades\Log::warning('Error checking tier for stamp redemption: ' . $e->getMessage());
                    }
                }

                $rewardValue = $baseRewardValue * $tierMultiplier;
                $this->stampDiscountAmount = $rewardValue;
            }

            // Close modal
            $this->showStampRedemptionModal = false;

            // Recalculate totals with stamp discount
            $this->calculateTotal();

            $this->alert('success', __('loyalty::app.stampRewardWillBeApplied'), [
                'toast' => true,
                'position' => 'top-end',
            ]);
        }
    }

    /**
     * Remove stamp redemption
     */
    public function removeStampRedemption()
    {
        if ($this->orderID) {
            // For existing orders, we'd need to remove the stamp redemption
            // This would require tracking which stamp rule was redeemed
            // For now, just clear the component state
            $this->selectedStampRuleId = null;
            $this->stampDiscountAmount = 0;

            // Recalculate totals
            $this->calculateTotal();
        } else {
            // For new orders, just clear the selection
            $this->selectedStampRuleId = null;
            $this->stampDiscountAmount = 0;

            // Recalculate totals
            $this->calculateTotal();
        }
    }

    /**
     * Check and automatically redeem stamps when a qualifying item is added
     * Only auto-redeems if customer is selected FIRST (before items are added)
     */
    protected function checkAndAutoRedeemStampsForItem($itemKey)
    {
        \Illuminate\Support\Facades\Log::info('checkAndAutoRedeemStampsForItem called', [
            'itemKey' => $itemKey,
            'customerId' => $this->customerId,
            'orderID' => $this->orderID,
            'selectedStampRuleId' => $this->selectedStampRuleId,
            'isLoyaltyEnabled' => $this->isLoyaltyEnabled(),
        ]);

        // CRITICAL: Only auto-redeem if customer is selected FIRST
        // If customer is not selected, don't auto-redeem (prevents issues when items added before customer)
        if (!$this->customerId || !$this->isLoyaltyEnabled()) {
            \Illuminate\Support\Facades\Log::info('Early return: no customer or loyalty not enabled');
            return;
        }

        // Check if stamps are enabled for POS platform
        if (!$this->isStampsEnabledForPOS()) {
            \Illuminate\Support\Facades\Log::info('Early return: stamps not enabled for POS');
            return;
        }

        // Allow re-evaluation even if a stamp rule was already selected.

        if (
            !module_enabled('Loyalty') ||
            !module_enabled('Loyalty')
        ) {
            \Illuminate\Support\Facades\Log::info('Early return: classes not found');
            return;
        }

        try {
            // Get the menu item ID
            $menuItemId = null;
            if (isset($this->orderItemVariation[$itemKey])) {
                $menuItemId = $this->orderItemVariation[$itemKey]->menu_item_id ?? null;
            } elseif (isset($this->orderItemList[$itemKey])) {
                $menuItemId = $this->orderItemList[$itemKey]->id ?? null;
            }

            if (!$menuItemId) {
                return;
            }

            // Check if this menu item has a stamp rule
            $restaurantId = restaurant()->id;
            $stampRule = \Modules\Loyalty\Entities\LoyaltyStampRule::getRuleForMenuItem($restaurantId, $menuItemId);

            if (!$stampRule || !$stampRule->is_active) {
                return;
            }

            // Check if customer has enough stamps for this rule
            $loyaltyService = app(\Modules\Loyalty\Services\LoyaltyService::class);
            $availableStamps = $loyaltyService->getAvailableStamps($restaurantId, $this->customerId, $stampRule->id);
            $stampsRequired = (int) ($stampRule->stamps_required ?? 1);

            // Include stamps already redeemed on this order so existing benefits remain applied
            $effectiveAvailableStamps = (int) $availableStamps;
            $orderIdForStamps = $this->orderID ?? ($this->orderDetail->id ?? null);
            if (!empty($orderIdForStamps)) {
                $redeemedStampsForOrder = (int) \Modules\Loyalty\Entities\LoyaltyStampTransaction::where('order_id', $orderIdForStamps)
                    ->where('stamp_rule_id', $stampRule->id)
                    ->where('type', 'REDEEM')
                    ->sum('stamps');
                $effectiveAvailableStamps += abs($redeemedStampsForOrder);
            }
            // Also include existing free item qty in the cart (for orders without transactions yet)
            if (property_exists($this, 'stampFreeItemKeys') && !empty($this->stampFreeItemKeys[$stampRule->id])) {
                $existingFreeQty = 0;
                foreach ($this->stampFreeItemKeys[$stampRule->id] as $freeKey) {
                    $existingFreeQty += (int) ($this->orderItemQty[$freeKey] ?? 0);
                }
                $effectiveAvailableStamps += $existingFreeQty * $stampsRequired;
            }

            $maxItemsByStamps = ($stampsRequired > 0) ? intdiv($effectiveAvailableStamps, $stampsRequired) : 0;

            // Determine eligible quantity for this stamp rule in current cart (exclude free items)
            $eligibleKeys = [];
            $eligibleQty = 0;
            foreach ($this->orderItemList as $key => $item) {
                if (strpos($key, 'free_stamp_') === 0) {
                    continue;
                }
                $itemMenuId = null;
                if (isset($this->orderItemVariation[$key])) {
                    $itemMenuId = $this->orderItemVariation[$key]->menu_item_id ?? null;
                } elseif (isset($this->orderItemList[$key])) {
                    $itemMenuId = $this->orderItemList[$key]->id ?? null;
                }
                if ($itemMenuId && (int) $itemMenuId === (int) $stampRule->menu_item_id) {
                    $eligibleKeys[] = $key;
                    $eligibleQty += (int) ($this->orderItemQty[$key] ?? 0);
                }
            }

            $itemsToRedeem = max(0, min($eligibleQty, $maxItemsByStamps));
            $shouldShowAlert = (!$this->selectedStampRuleId && $itemsToRedeem > 0);

            if ($itemsToRedeem <= 0) {
                // Remove any free items for this rule if no longer eligible
                foreach (array_keys($this->orderItemList) as $key) {
                    if (strpos($key, 'free_stamp_' . $stampRule->id) === 0) {
                        unset($this->orderItemList[$key], $this->orderItemQty[$key], $this->orderItemAmount[$key], $this->orderItemVariation[$key], $this->itemModifiersSelected[$key], $this->itemNotes[$key], $this->orderItemModifiersPrice[$key], $this->orderItemTaxDetails[$key]);
                    }
                }
                $this->stampDiscountAmount = 0;
                $this->selectedStampRuleId = null;
                $this->calculateTotal();
                return;
            }

            $this->selectedStampRuleId = $stampRule->id;

            if ($stampRule->reward_type === 'free_item') {
                if ($stampRule->rewardMenuItem) {
                    $freeItemKeys = property_exists($this, 'stampFreeItemKeys') ? ($this->stampFreeItemKeys[$stampRule->id] ?? []) : [];

                    // Prefer existing free items from KOT/order details if present
                    if (!empty($freeItemKeys)) {
                        foreach ($freeItemKeys as $freeKey) {
                            $this->orderItemQty[$freeKey] = $itemsToRedeem;
                            $this->orderItemAmount[$freeKey] = 0;
                        }
                    } else {
                        $freeItemKey = null;
                        foreach (array_keys($this->orderItemList) as $key) {
                            if (strpos($key, 'free_stamp_' . $stampRule->id) === 0) {
                                $freeItemKey = $key;
                                break;
                            }
                        }
                        if (!$freeItemKey) {
                            $freeItemKey = 'free_stamp_' . $stampRule->id . '_' . time();
                            $this->orderItemList[$freeItemKey] = $stampRule->rewardMenuItem;
                            $this->orderItemAmount[$freeItemKey] = 0;
                            $this->orderItemModifiersPrice[$freeItemKey] = 0;
                            $this->itemModifiersSelected[$freeItemKey] = [];
                            $this->itemNotes[$freeItemKey] = __('loyalty::app.freeItemFromStamp');
                            if ($stampRule->reward_menu_item_variation_id && $stampRule->rewardMenuItemVariation) {
                                $this->orderItemVariation[$freeItemKey] = $stampRule->rewardMenuItemVariation;
                            } else {
                                $this->orderItemVariation[$freeItemKey] = null;
                            }
                            if (property_exists($this, 'stampFreeItemKeys')) {
                                $this->stampFreeItemKeys[$stampRule->id][] = $freeItemKey;
                            }
                        }
                        $this->orderItemQty[$freeItemKey] = $itemsToRedeem;
                    }
                }
                $this->stampDiscountAmount = 0;
            } else {
                // Apply discount to eligible items based on quantities and available stamps
                $tierMultiplier = 1.00;
                if ($this->customerId && module_enabled('Loyalty')) {
                    try {
                        $account = $loyaltyService->getOrCreateAccount(restaurant()->id, $this->customerId);
                        if ($account && $account->tier_id) {
                            $tier = \Modules\Loyalty\Entities\LoyaltyTier::find($account->tier_id);
                            if ($tier && $tier->redemption_multiplier > 0) {
                                $tierMultiplier = $tier->redemption_multiplier;
                            }
                        }
                    } catch (\Exception $e) {
                        \Illuminate\Support\Facades\Log::warning('Error checking tier for stamp redemption: ' . $e->getMessage());
                    }
                }

                // Reset base amounts for eligible items before applying discount
                foreach ($eligibleKeys as $key) {
                    $basePrice = $this->orderItemVariation[$key]->price ?? $this->orderItemList[$key]->price;
                    $modifierPrice = $this->orderItemModifiersPrice[$key] ?? 0;
                    $qty = (int) ($this->orderItemQty[$key] ?? 1);
                    $this->orderItemAmount[$key] = round(($basePrice + $modifierPrice) * $qty, 2);
                }

                $remainingUnits = $itemsToRedeem;
                $totalDiscountApplied = 0;
                foreach ($eligibleKeys as $key) {
                    if ($remainingUnits <= 0) {
                        break;
                    }
                    $qty = (int) ($this->orderItemQty[$key] ?? 1);
                    if ($qty <= 0) {
                        continue;
                    }
                    $unitsToRedeem = min($qty, $remainingUnits);
                    $basePrice = $this->orderItemVariation[$key]->price ?? $this->orderItemList[$key]->price;
                    $modifierPrice = $this->orderItemModifiersPrice[$key] ?? 0;
                    $unitAmount = $basePrice + $modifierPrice;
                    $unitDiscount = 0;
                    if ($stampRule->reward_type === 'discount_percent') {
                        $unitDiscount = ($unitAmount * $stampRule->reward_value) / 100;
                    } elseif ($stampRule->reward_type === 'discount_amount') {
                        $unitDiscount = min($stampRule->reward_value, $unitAmount);
                    }
                    $unitDiscount = $unitDiscount * $tierMultiplier;
                    $itemDiscount = round($unitDiscount * $unitsToRedeem, 2);
                    $this->orderItemAmount[$key] = round(max(0, ($this->orderItemAmount[$key] ?? 0) - $itemDiscount), 2);
                    $totalDiscountApplied += $itemDiscount;
                    $remainingUnits -= $unitsToRedeem;
                }

                $this->stampDiscountAmount = round($totalDiscountApplied, 2);

                // Do not alter item notes for stamp discounts (display amount only in UI badge)
            }

            $this->calculateTotal();

            if ($shouldShowAlert) {
                $rewardMessage = $stampRule->reward_type === 'free_item'
                    ? ($stampRule->rewardMenuItem ? $stampRule->rewardMenuItem->item_name : __('loyalty::app.freeItem'))
                    : __('loyalty::app.discount');
                $itemName = $stampRule->menuItem ? $stampRule->menuItem->item_name : __('loyalty::app.unknownItem');
                $this->alert('success', __('loyalty::app.stampAutoRedeemed', [
                    'item' => $itemName,
                    'reward' => $rewardMessage
                ]), [
                    'toast' => true,
                    'position' => 'top-end',
                    'timer' => 5000,
                ]);
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to check auto-redeem stamps: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            // Silently fail - don't interrupt item addition
        }
    }

    protected function redeemLoyaltyPointsAfterOrderCreation(\App\Models\Order $order, string $status): void
    {
        if (!module_enabled('Loyalty')) {
            // Module doesn't exist - clear loyalty redemption
            $order->update([
                'loyalty_points_redeemed' => 0,
                'loyalty_discount_amount' => 0,
            ]);
            $this->loyaltyPointsRedeemed = 0;
            $this->loyaltyDiscountAmount = 0;
            // Recalculate total without loyalty discount
            $this->calculateTotal();
            return;
        }

        try {
            $loyaltyService = app(\Modules\Loyalty\Services\LoyaltyService::class);

            // Call redeemPoints - this WILL deduct points and update loyalty_discount_amount
            $result = $loyaltyService->redeemPoints($order, $this->loyaltyPointsRedeemed);

            if ($result['success']) {
                // Refresh to get updated loyalty_discount_amount from service
                $order->refresh();

                // NOW recalculate total with the service's discount amount
                $order->load(['taxes', 'charges.charge']);

                $correctTotal = $order->sub_total;
                $correctTotal -= ($order->discount_amount ?? 0);
                $correctTotal -= ($order->loyalty_discount_amount ?? 0); // Service calculated this
                // NOTE: Stamp discount is NOT subtracted here because when stamps are auto-redeemed in POS,
                // the discount is already applied to item amounts, so sub_total already reflects it

                $discountedBase = $correctTotal;

                // Step 1: Calculate service charges on discounted base
                $serviceTotal = 0;
                if ($order->charges && $order->charges->count() > 0) {
                    foreach ($order->charges as $chargeRelation) {
                        if ($chargeRelation->charge) {
                            $serviceTotal += $chargeRelation->charge->getAmount($discountedBase);
                        }
                    }
                }

                // Step 2: Calculate tax_base based on setting
                // Tax base = (subtotal - discounts) + service charges (if enabled)
                $includeChargesInTaxBase = restaurant()->include_charges_in_tax_base ?? true;
                $taxBase = $includeChargesInTaxBase ? ($discountedBase + $serviceTotal) : $discountedBase;

                // Step 3: Recalculate taxes on tax_base
                $correctTaxAmount = 0;
                if ($order->tax_mode === 'order' && $order->taxes && $order->taxes->count() > 0) {
                    foreach ($order->taxes as $tax) {
                        $correctTaxAmount += ($tax->tax_percent / 100) * $taxBase;
                    }
                } else {
                    $correctTaxAmount = $order->total_tax_amount ?? 0;
                }
                $correctTotal += $correctTaxAmount;

                // Step 4: Add service charges to total
                $correctTotal += $serviceTotal;

                // Add tip and delivery
                $correctTotal += ($order->tip_amount ?? 0);
                $correctTotal += ($order->delivery_fee ?? 0);

                // FORCE UPDATE total - this is critical!
                // Preserve stamp_discount_amount and loyalty values if they were also redeemed
                $updateData = [
                    'total' => round($correctTotal, 2),
                    'total_tax_amount' => round($correctTaxAmount, 2),
                ];

                // Preserve stamp discount if it exists
                if ($order->stamp_discount_amount > 0) {
                    $updateData['stamp_discount_amount'] = $order->stamp_discount_amount;
                }

                // CRITICAL: Preserve loyalty values (they were just set by redeemPoints)
                if ($order->loyalty_points_redeemed > 0) {
                    $updateData['loyalty_points_redeemed'] = $order->loyalty_points_redeemed;
                }
                if ($order->loyalty_discount_amount > 0) {
                    $updateData['loyalty_discount_amount'] = $order->loyalty_discount_amount;
                }

                \Illuminate\Support\Facades\Log::info('BEFORE DB UPDATE - POINTS', [
                    'order_id' => $order->id,
                    'update_data' => $updateData,
                ]);

                \Illuminate\Support\Facades\DB::table('orders')->where('id', $order->id)->update($updateData);

                $order->refresh();

                // CRITICAL: Update component's total to match database
                $this->total = $order->total;
                $this->subTotal = $order->sub_total;
                $this->totalTaxAmount = $order->total_tax_amount;

                \Illuminate\Support\Facades\Log::info('LOYALTY REDEEMED - FINAL', [
                    'order_id' => $order->id,
                    'points' => $order->loyalty_points_redeemed,
                    'discount' => $order->loyalty_discount_amount,
                    'stamp_discount' => $order->stamp_discount_amount,
                    'total' => $order->total,
                    'subtotal' => $order->sub_total,
                    'component_total' => $this->total,
                ]);

                // FINAL VERIFICATION: Check what's actually in the database
                $dbOrder = \Illuminate\Support\Facades\DB::table('orders')->where('id', $order->id)->first();
                \Illuminate\Support\Facades\Log::info('DB ORDER - VERIFICATION', [
                    'order_id' => $order->id,
                    'db_loyalty_points_redeemed' => $dbOrder->loyalty_points_redeemed ?? 'NULL',
                    'db_loyalty_discount_amount' => $dbOrder->loyalty_discount_amount ?? 'NULL',
                    'db_stamp_discount_amount' => $dbOrder->stamp_discount_amount ?? 'NULL',
                    'db_total' => $dbOrder->total ?? 'NULL',
                    'db_sub_total' => $dbOrder->sub_total ?? 'NULL',
                ]);
            } else {
                // Redemption failed - clear discount and recalculate total
                \Illuminate\Support\Facades\Log::error('LOYALTY REDEEM FAILED', [
                    'order_id' => $order->id,
                    'error' => $result['message'] ?? 'Unknown',
                ]);

                // Clear loyalty redemption from order
                $order->update([
                    'loyalty_points_redeemed' => 0,
                    'loyalty_discount_amount' => 0,
                ]);

                // Recalculate total WITHOUT loyalty discount
                $order->refresh();
                $order->load(['taxes', 'charges.charge']);

                $correctTotal = $order->sub_total;
                $correctTotal -= ($order->discount_amount ?? 0);
                $discountedBase = $correctTotal;

                $correctTaxAmount = 0;
                if ($order->tax_mode === 'order' && $order->taxes && $order->taxes->count() > 0) {
                    foreach ($order->taxes as $tax) {
                        $correctTaxAmount += ($tax->tax_percent / 100) * $discountedBase;
                    }
                } else {
                    $correctTaxAmount = $order->total_tax_amount ?? 0;
                }
                $correctTotal += $correctTaxAmount;

                if ($order->charges && $order->charges->count() > 0) {
                    foreach ($order->charges as $chargeRelation) {
                        if ($chargeRelation->charge) {
                            $correctTotal += $chargeRelation->charge->getAmount($discountedBase);
                        }
                    }
                }

                $correctTotal += ($order->tip_amount ?? 0);
                $correctTotal += ($order->delivery_fee ?? 0);

                // Update total without loyalty discount
                \Illuminate\Support\Facades\DB::table('orders')->where('id', $order->id)->update([
                    'total' => round($correctTotal, 2),
                    'total_tax_amount' => round($correctTaxAmount, 2),
                ]);

                // Clear component properties
                $this->loyaltyPointsRedeemed = 0;
                $this->loyaltyDiscountAmount = 0;

                // Show error to user
                $this->alert('error', $result['message'] ?? __('loyalty::app.failedToRedeemPoints'), [
                    'toast' => true,
                    'position' => 'top-end',
                ]);
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('LOYALTY EXCEPTION', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }


    protected function redeemStampsAfterOrderCreation(\App\Models\Order $order, string $status): void
    {
        try {
            if (module_enabled('Loyalty')) {
                $loyaltyService = app(\Modules\Loyalty\Services\LoyaltyService::class);
                $result = $loyaltyService->redeemStamps($order, $this->selectedStampRuleId);

                if ($result['success']) {
                    // Reload order to get updated items/discounts
                    $order->refresh();
                    $order->load('items');

                    // Update stamp discount amount
                    $this->stampDiscountAmount = $order->stamp_discount_amount ?? 0;

                    // If reward was free_item, items were added to order
                    // We need to add the free item to the cart display
                    if ($result['reward_type'] === 'free_item' && isset($result['reward_menu_item_id'])) {
                        // Find the free item that was just added
                        $freeItem = $order->items()
                            ->where('menu_item_id', $result['reward_menu_item_id'])
                            ->where('is_free_item_from_stamp', true)
                            ->where('stamp_rule_id', $this->selectedStampRuleId)
                            ->latest()
                            ->first();

                        if ($freeItem && $freeItem->menuItem) {
                            // Add free item to cart display
                            $freeItemKey = 'free_stamp_' . $this->selectedStampRuleId . '_' . time();
                            $this->orderItemList[$freeItemKey] = $freeItem->menuItem;
                            $this->orderItemQty[$freeItemKey] = $freeItem->quantity;
                            $this->orderItemAmount[$freeItemKey] = 0; // Free item

                            // Set variation if it exists on the order item
                            if ($freeItem->menu_item_variation_id && $freeItem->menuItemVariation) {
                                $this->orderItemVariation[$freeItemKey] = $freeItem->menuItemVariation;
                            } else {
                                $this->orderItemVariation[$freeItemKey] = null;
                            }

                            // Recalculate total to include the free item in display
                            $this->calculateTotal();
                        }
                    }

                    // Recalculate totals after stamp redemption
                    $this->recalculateOrderTotalAfterStampRedemption($order);

                    // Log after stamp redemption
                    $order->refresh();
                    \Illuminate\Support\Facades\Log::info('AFTER STAMP REDEMPTION', [
                        'order_id' => $order->id,
                        'loyalty_points_redeemed' => $order->loyalty_points_redeemed,
                        'loyalty_discount_amount' => $order->loyalty_discount_amount,
                        'stamp_discount_amount' => $order->stamp_discount_amount,
                        'sub_total' => $order->sub_total,
                        'total' => $order->total,
                    ]);
                } else {
                    \Illuminate\Support\Facades\Log::error('STAMP REDEEM FAILED', [
                        'order_id' => $order->id,
                        'error' => $result['message'] ?? 'Unknown',
                    ]);

                    $this->alert('error', $result['message'], [
                        'toast' => true,
                        'position' => 'top-end',
                    ]);
                }
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to redeem stamps on order creation: ' . $e->getMessage());
        }
    }

    protected function applyStampDiscountToOrderData(array &$orderData): void
    {
        if ($this->selectedStampRuleId && $this->stampDiscountAmount > 0 && module_enabled('Loyalty')) {
            $stampRule = \Modules\Loyalty\Entities\LoyaltyStampRule::find($this->selectedStampRuleId);
            if ($stampRule && in_array($stampRule->reward_type, ['discount_percent', 'discount_amount'])) {
                // Discount was already applied to item, so save it to order
                $orderData['stamp_discount_amount'] = $this->stampDiscountAmount;
            }
        }
    }


    protected function ensureTotalsIncludeLoyaltyBeforeUpdate(): void
    {
        // Ensure total includes loyalty discount before updating
        // Recalculate if loyalty points are redeemed to ensure discount is applied
        if ($this->loyaltyPointsRedeemed > 0 && $this->loyaltyDiscountAmount > 0) {
            // Recalculate total to ensure loyalty discount is included
            $this->calculateTotal();

            // FINAL VERIFICATION: Ensure discount is in the total before updating
            // Calculate expected total: subtotal + taxes + charges + tip + delivery - loyalty discount
            $expectedTotal = $this->subTotal;

            // Add taxes (already calculated in calculateTotal)
            $expectedTotal += $this->totalTaxAmount;

            // Add extra charges
            if (!empty($this->extraCharges)) {
                foreach ($this->extraCharges as $charge) {
                    $expectedTotal += $charge->getAmount($this->discountedTotal);
                }
            }

            // Add tip and delivery
            $expectedTotal += ($this->tipAmount ?? 0);
            $expectedTotal += ($this->deliveryFee ?? 0);

            // Subtract loyalty discount
            $expectedTotal -= $this->loyaltyDiscountAmount;

            // If totals don't match, use the calculated expected total
            if (abs($this->total - $expectedTotal) > 0.01) {
                $this->total = $expectedTotal;
            }
        }
    }

    protected function handleExistingOrderLoyaltyRedemption(\App\Models\Order $order, int $existingRedeemedPoints = 0): void
    {
        // CRITICAL: Deduct loyalty points from customer account AND recalculate total (for existing order)
        // Only if points are enabled for POS
        if ($this->loyaltyPointsRedeemed > 0 && $this->loyaltyDiscountAmount > 0 && $order->customer_id && $this->isPointsEnabledForPOS()) {
            if ($existingRedeemedPoints == 0) {
                // Points not redeemed yet - redeem them now
                if (!module_enabled('Loyalty')) {
                    // Module doesn't exist - clear loyalty redemption
                    $order->update([
                        'loyalty_points_redeemed' => 0,
                        'loyalty_discount_amount' => 0,
                    ]);
                    $this->loyaltyPointsRedeemed = 0;
                    $this->loyaltyDiscountAmount = 0;
                    return;
                }

                try {
                    $loyaltyService = app(\Modules\Loyalty\Services\LoyaltyService::class);
                    $result = $loyaltyService->redeemPoints($order, $this->loyaltyPointsRedeemed);

                    if (!$result['success']) {
                        // Redemption failed - clear discount and recalculate total
                        \Illuminate\Support\Facades\Log::error('FAILED to redeem loyalty points', [
                            'order_id' => $order->id,
                            'points' => $this->loyaltyPointsRedeemed,
                            'error' => $result['message'] ?? 'Unknown error',
                        ]);

                        // Clear loyalty redemption from order
                        $order->update([
                            'loyalty_points_redeemed' => 0,
                            'loyalty_discount_amount' => 0,
                        ]);

                        // Recalculate total WITHOUT loyalty discount
                        $order->refresh();
                        $order->load(['taxes', 'charges.charge']);

                        $correctTotal = $order->sub_total;
                        $correctTotal -= ($order->discount_amount ?? 0);
                        $discountedBase = $correctTotal;

                        $correctTaxAmount = 0;
                        if ($order->tax_mode === 'order' && $order->taxes && $order->taxes->count() > 0) {
                            foreach ($order->taxes as $tax) {
                                $correctTaxAmount += ($tax->tax_percent / 100) * $discountedBase;
                            }
                        } else {
                            $correctTaxAmount = $order->total_tax_amount ?? 0;
                        }
                        $correctTotal += $correctTaxAmount;

                        if ($order->charges && $order->charges->count() > 0) {
                            foreach ($order->charges as $chargeRelation) {
                                if ($chargeRelation->charge) {
                                    $correctTotal += $chargeRelation->charge->getAmount($discountedBase);
                                }
                            }
                        }

                        $correctTotal += ($order->tip_amount ?? 0);
                        $correctTotal += ($order->delivery_fee ?? 0);

                        // Update total without loyalty discount
                        \Illuminate\Support\Facades\DB::table('orders')->where('id', $order->id)->update([
                            'total' => round($correctTotal, 2),
                            'total_tax_amount' => round($correctTaxAmount, 2),
                        ]);

                        // Clear component properties
                        $this->loyaltyPointsRedeemed = 0;
                        $this->loyaltyDiscountAmount = 0;

                        // Show error to user
                        $this->alert('error', $result['message'] ?? __('loyalty::app.failedToRedeemPoints'), [
                            'toast' => true,
                            'position' => 'top-end',
                        ]);
                    } else {
                        // Points deducted - recalculate total
                        $order->refresh();
                        $order->load(['taxes', 'charges.charge']);

                        $finalTotal = $order->sub_total;
                        $finalTotal -= ($order->discount_amount ?? 0);
                        $finalTotal -= ($order->loyalty_discount_amount ?? 0);
                        $discountedSubtotal = $finalTotal;

                        $finalTaxAmount = 0;
                        if ($order->tax_mode === 'order' && $order->taxes && $order->taxes->count() > 0) {
                            foreach ($order->taxes as $tax) {
                                $finalTaxAmount += ($tax->tax_percent / 100) * $discountedSubtotal;
                            }
                        } else {
                            $finalTaxAmount = $order->total_tax_amount ?? 0;
                        }
                        $finalTotal += $finalTaxAmount;

                        if ($order->charges && $order->charges->count() > 0) {
                            foreach ($order->charges as $chargeRelation) {
                                if ($chargeRelation->charge) {
                                    $finalTotal += $chargeRelation->charge->getAmount($discountedSubtotal);
                                }
                            }
                        }

                        $finalTotal += ($order->tip_amount ?? 0);
                        $finalTotal += ($order->delivery_fee ?? 0);

                        // FORCE UPDATE total using DB directly
                        \Illuminate\Support\Facades\DB::table('orders')->where('id', $order->id)->update([
                            'total' => round($finalTotal, 2),
                            'total_tax_amount' => round($finalTaxAmount, 2),
                        ]);

                        $order->refresh();

                        // CRITICAL: Update component's total to match database
                        $this->total = $order->total;

                        \Illuminate\Support\Facades\Log::info('LOYALTY REDEEMED (UPDATE)', [
                            'order_id' => $order->id,
                            'points' => $order->loyalty_points_redeemed,
                            'discount' => $order->loyalty_discount_amount,
                            'total' => $order->total,
                            'subtotal' => $order->sub_total,
                        ]);
                    }
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('EXCEPTION redeeming loyalty points', [
                        'order_id' => $order->id,
                        'points' => $this->loyaltyPointsRedeemed,
                        'error' => $e->getMessage(),
                    ]);
                }
            } elseif ($existingRedeemedPoints != $this->loyaltyPointsRedeemed) {
                // Points changed - remove old redemption and add new one
                try {
                    $loyaltyService = app(\Modules\Loyalty\Services\LoyaltyService::class);
                    $loyaltyService->removeRedemption($order);
                    $result = $loyaltyService->redeemPoints($order, $this->loyaltyPointsRedeemed);

                    if ($result['success']) {
                        $order->refresh();
                        $order->load(['taxes', 'charges.charge']);

                        $finalTotal = $order->sub_total;
                        $finalTotal -= ($order->discount_amount ?? 0);
                        $finalTotal -= ($order->loyalty_discount_amount ?? 0);
                        $discountedSubtotal = $finalTotal;

                        $finalTaxAmount = 0;
                        if ($order->tax_mode === 'order' && $order->taxes && $order->taxes->count() > 0) {
                            foreach ($order->taxes as $tax) {
                                $finalTaxAmount += ($tax->tax_percent / 100) * $discountedSubtotal;
                            }
                        } else {
                            $finalTaxAmount = $order->total_tax_amount ?? 0;
                        }
                        $finalTotal += $finalTaxAmount;

                        if ($order->charges && $order->charges->count() > 0) {
                            foreach ($order->charges as $chargeRelation) {
                                if ($chargeRelation->charge) {
                                    $finalTotal += $chargeRelation->charge->getAmount($discountedSubtotal);
                                }
                            }
                        }

                        $finalTotal += ($order->tip_amount ?? 0);
                        $finalTotal += ($order->delivery_fee ?? 0);

                        $order->update([
                            'total' => round($finalTotal, 2),
                            'total_tax_amount' => round($finalTaxAmount, 2),
                        ]);
                    }
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('EXCEPTION updating loyalty redemption', [
                        'order_id' => $order->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        } elseif ($existingRedeemedPoints > 0 && $this->loyaltyPointsRedeemed == 0) {
            // Points were redeemed but now removed - remove redemption
            try {
                $loyaltyService = app(\Modules\Loyalty\Services\LoyaltyService::class);
                $loyaltyService->removeRedemption($order);
                $order->refresh();
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('EXCEPTION removing loyalty redemption', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }


    protected function handleDraftOrderLoyaltyRedemption(\App\Models\Order $order): void
    {
        // CRITICAL: Redeem stamps and points NOW that items exist (for draft orders)
        $loyaltyRedemptionHappened = false;
        if ($order->customer_id) {
            // Redeem stamps ONLY if they were actually applied in POS
            // Don't auto-redeem just because items have stamp_rule_id set
            if ($this->isStampsEnabledForPOS() && module_enabled('Loyalty')) {
                try {
                    // CRITICAL: Only redeem stamps if they were explicitly applied in POS
                    // Check for:
                    // 1. selectedStampRuleId is set (user explicitly selected stamps)
                    // 2. Items have discounts applied (amount < price * quantity) - indicates stamp discount was applied
                    // 3. Free items exist in cart arrays (free items from stamps)

                    $stampRuleIdsToRedeem = [];

                    // Check 1: selectedStampRuleId (explicit selection)
                    if ($this->selectedStampRuleId) {
                        $stampRuleIdsToRedeem[] = $this->selectedStampRuleId;
                    }

                    // Check 2: Items with discounts applied (stamp discounts reduce amount)
                    $order->load('items');
                    $itemsWithStampDiscounts = [];
                    foreach ($order->items as $orderItem) {
                        // Skip free items (they're handled separately)
                        if ($orderItem->is_free_item_from_stamp ?? false) {
                            continue;
                        }

                        // Check if item has a discount applied (amount < expected)
                        $expectedAmount = (float)($orderItem->price ?? 0) * (int)($orderItem->quantity ?? 1);
                        $actualAmount = (float)($orderItem->amount ?? 0);

                        // If actual amount is significantly less than expected, discount was applied
                        if ($expectedAmount > $actualAmount + 0.01 && $orderItem->stamp_rule_id) {
                            if (!in_array($orderItem->stamp_rule_id, $stampRuleIdsToRedeem)) {
                                $stampRuleIdsToRedeem[] = $orderItem->stamp_rule_id;
                                $itemsWithStampDiscounts[] = $orderItem->id;
                            }
                        }
                    }

                    // Check 3: Free items in cart arrays (indicates stamps were applied)
                    foreach ($this->orderItemList ?? [] as $key => $item) {
                        if (strpos($key, 'free_stamp_') === 0) {
                            // Extract stamp_rule_id from free item key or item
                            $parts = explode('_', $key);
                            if (count($parts) >= 3 && $parts[0] === 'free' && $parts[1] === 'stamp') {
                                $stampRuleId = (int)($parts[2] ?? 0);
                                if ($stampRuleId > 0 && !in_array($stampRuleId, $stampRuleIdsToRedeem)) {
                                    $stampRuleIdsToRedeem[] = $stampRuleId;
                                }
                            }
                        }
                    }

                    // Also check order items that are marked as free from stamps
                    foreach ($order->items as $orderItem) {
                        if (($orderItem->is_free_item_from_stamp ?? false) && $orderItem->stamp_rule_id) {
                            if (!in_array($orderItem->stamp_rule_id, $stampRuleIdsToRedeem)) {
                                $stampRuleIdsToRedeem[] = $orderItem->stamp_rule_id;
                            }
                        }
                    }

                    \Illuminate\Support\Facades\Log::info('Collecting stamp rules for redemption (draft order)', [
                        'order_id' => $order->id,
                        'selected_stamp_rule_id' => $this->selectedStampRuleId,
                        'stamp_rule_ids_to_redeem' => $stampRuleIdsToRedeem,
                        'items_with_stamp_discounts' => $itemsWithStampDiscounts,
                        'total_items' => $order->items->count(),
                    ]);

                    // Only redeem if stamps were actually applied
                    if (empty($stampRuleIdsToRedeem)) {
                        \Illuminate\Support\Facades\Log::info('No stamps to redeem - stamps were not applied in POS', [
                            'order_id' => $order->id,
                        ]);
                        // Skip stamp redemption - no stamps were applied in POS
                    } else {
                        // Redeem stamps for each stamp rule ONCE
                        foreach ($stampRuleIdsToRedeem as $stampRuleIdToRedeem) {
                            if (!$stampRuleIdToRedeem) {
                                continue;
                            }

                            \Illuminate\Support\Facades\Log::info('Processing stamp rule for redemption (draft order)', [
                                'order_id' => $order->id,
                                'stamp_rule_id' => $stampRuleIdToRedeem,
                            ]);

                            try {
                                // Redeem stamps for all eligible items on this draft order
                                $this->redeemStampsForAllEligibleItems($order, $stampRuleIdToRedeem);
                            } catch (\Exception $e) {
                                \Illuminate\Support\Facades\Log::error('Failed to redeem stamps for stamp rule in draft order', [
                                    'order_id' => $order->id,
                                    'stamp_rule_id' => $stampRuleIdToRedeem,
                                    'error' => $e->getMessage(),
                                    'trace' => $e->getTraceAsString(),
                                ]);
                                // Continue with next stamp rule even if this one fails
                            }
                        }

                        $loyaltyRedemptionHappened = true;
                    }
                    $order->refresh();
                    // Recalculate totals after stamp redemption
                    $this->recalculateOrderTotalAfterStampRedemption($order);
                    $order->refresh();

                    // Update component values
                    $this->stampDiscountAmount = $order->stamp_discount_amount ?? 0;
                    $this->subTotal = $order->sub_total;
                    $this->total = $order->total;
                    $this->totalTaxAmount = $order->total_tax_amount;
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('Failed to redeem stamps for draft order: ' . $e->getMessage());
                }
            }

            // Redeem points if selected
            if ($this->loyaltyPointsRedeemed > 0 && $this->loyaltyDiscountAmount > 0 && $this->isPointsEnabledForPOS() && module_enabled('Loyalty')) {
                try {
                    $loyaltyService = app(\Modules\Loyalty\Services\LoyaltyService::class);
                    $result = $loyaltyService->redeemPoints($order, $this->loyaltyPointsRedeemed);

                    if ($result['success']) {
                        $loyaltyRedemptionHappened = true;
                        $order->refresh();
                        $order->load(['taxes', 'charges.charge']);

                        // Recalculate total with loyalty discount
                        $correctTotal = $order->sub_total;
                        $correctTotal -= ($order->discount_amount ?? 0);
                        $correctTotal -= ($order->loyalty_discount_amount ?? 0);
                        // NOTE: Stamp discount is NOT subtracted here because when stamps are auto-redeemed in POS,
                        // the discount is already applied to item amounts, so sub_total already reflects it

                        $discountedBase = $correctTotal;

                        // Step 1: Calculate service charges on discounted base
                        $serviceTotal = 0;
                        if ($order->charges && $order->charges->count() > 0) {
                            foreach ($order->charges as $chargeRelation) {
                                if ($chargeRelation->charge) {
                                    $serviceTotal += $chargeRelation->charge->getAmount($discountedBase);
                                }
                            }
                        }

                        // Step 2: Calculate tax_base based on setting
                        // Tax base = (subtotal - discounts) + service charges (if enabled)
                        $includeChargesInTaxBase = restaurant()->include_charges_in_tax_base ?? true;
                        $taxBase = $includeChargesInTaxBase ? ($discountedBase + $serviceTotal) : $discountedBase;

                        // Step 3: Recalculate taxes on tax_base
                        $correctTaxAmount = 0;
                        if ($order->tax_mode === 'order' && $order->taxes && $order->taxes->count() > 0) {
                            foreach ($order->taxes as $orderTax) {
                                $tax = $orderTax->tax;
                                if ($tax && isset($tax->tax_percent)) {
                                    $correctTaxAmount += ($tax->tax_percent / 100) * $taxBase;
                                }
                            }
                        } else {
                            $correctTaxAmount = $order->items->sum('tax_amount') ?? 0;
                        }
                        $correctTotal += $correctTaxAmount;

                        // Step 4: Add service charges to total
                        $correctTotal += $serviceTotal;

                        // Add tip and delivery
                        $correctTotal += ($order->tip_amount ?? 0);
                        $correctTotal += ($order->delivery_fee ?? 0);

                        // Update order with all values preserved
                        $updateData = [
                            'sub_total' => $this->subTotal,
                            'total' => round($correctTotal, 2),
                            'discount_amount' => $this->discountAmount,
                            'total_tax_amount' => round($correctTaxAmount, 2),
                            'tax_mode' => $this->taxMode,
                        ];

                        if ($order->stamp_discount_amount > 0) {
                            $updateData['stamp_discount_amount'] = $order->stamp_discount_amount;
                        }
                        if ($order->loyalty_points_redeemed > 0) {
                            $updateData['loyalty_points_redeemed'] = $order->loyalty_points_redeemed;
                        }
                        if ($order->loyalty_discount_amount > 0) {
                            $updateData['loyalty_discount_amount'] = $order->loyalty_discount_amount;
                        }

                        \App\Models\Order::where('id', $order->id)->update($updateData);
                        $order->refresh();

                        // Update component values
                        $this->total = $order->total;
                        $this->totalTaxAmount = $order->total_tax_amount;
                    }
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('Failed to redeem points for draft order: ' . $e->getMessage());
                }
            }
        }

        // CRITICAL: Update order with tax amount for draft orders (if loyalty redemption didn't happen)
        if (!$loyaltyRedemptionHappened) {
            \App\Models\Order::where('id', $order->id)->update([
                'total_tax_amount' => $this->totalTaxAmount,
                'tax_mode' => $this->taxMode,
            ]);
        }
    }


    protected function handleKotOrderLoyaltyRedemption(\App\Models\Order $order): void
    {
        if (!$order->customer_id) {
            return;
        }

        $stampRuleIdsToRedeem = [];

        // Check 1: selectedStampRuleId (explicit selection)
        if ($this->selectedStampRuleId) {
            $stampRuleIdsToRedeem[] = $this->selectedStampRuleId;
        }

        // Check 2: Items with discounts applied (stamp discounts reduce amount)
        $order->load('items');
        foreach ($order->items as $orderItem) {
            // Skip free items (they're handled separately)
            if ($orderItem->is_free_item_from_stamp ?? false) {
                if ($orderItem->stamp_rule_id && !in_array($orderItem->stamp_rule_id, $stampRuleIdsToRedeem)) {
                    $stampRuleIdsToRedeem[] = $orderItem->stamp_rule_id;
                }
                continue;
            }

            // Check if item has a discount applied (amount < expected)
            $expectedAmount = (float)($orderItem->price ?? 0) * (int)($orderItem->quantity ?? 1);
            $actualAmount = (float)($orderItem->amount ?? 0);

            // If actual amount is significantly less than expected, discount was applied
            if ($expectedAmount > $actualAmount + 0.01 && $orderItem->stamp_rule_id) {
                if (!in_array($orderItem->stamp_rule_id, $stampRuleIdsToRedeem)) {
                    $stampRuleIdsToRedeem[] = $orderItem->stamp_rule_id;
                }
            }
        }

        // Check 3: Free items in kot_items (indicates stamps were applied)
        foreach ($order->kot as $kot) {
            foreach ($kot->items as $kotItem) {
                if (($kotItem->is_free_item_from_stamp ?? false) && $kotItem->stamp_rule_id) {
                    if (!in_array($kotItem->stamp_rule_id, $stampRuleIdsToRedeem)) {
                        $stampRuleIdsToRedeem[] = $kotItem->stamp_rule_id;
                    }
                }
            }
        }

        // Only redeem if stamps were actually applied in POS
        if (!empty($stampRuleIdsToRedeem)) {
            // Redeem stamps for each stamp rule ONCE
            // The helper method handles all duplicate checking internally
            foreach ($stampRuleIdsToRedeem as $stampRuleIdToRedeem) {
                if (!$stampRuleIdToRedeem || !$this->isStampsEnabledForPOS()) {
                    continue;
                }

                // CRITICAL: Call ONCE per stamp rule - the helper method handles duplicate prevention
                $this->redeemStampsForAllEligibleItems($order, $stampRuleIdToRedeem);

                $order->refresh();
                $order->load('items');
            }
        }

        // Redeem only additional points beyond what was already redeemed on this order.
        $alreadyRedeemedPoints = (int) ($order->loyalty_points_redeemed ?? 0);
        $requestedPoints = (int) ($this->loyaltyPointsRedeemed ?? 0);
        $additionalPointsToRedeem = max(0, $requestedPoints - $alreadyRedeemedPoints);

        if ($additionalPointsToRedeem > 0 && $this->loyaltyDiscountAmount > 0 && $this->isPointsEnabledForPOS()) {
            $loyaltyService = app(\Modules\Loyalty\Services\LoyaltyService::class);
            $result = $loyaltyService->redeemPoints($order, $additionalPointsToRedeem);

            if ($result['success']) {
                $order->refresh();
                $order->load(['taxes', 'charges.charge', 'items']);
            }
        }
    }

    protected function getOrderLoyaltyDiscount(\App\Models\Order $order): float
    {
        return (float)($order->loyalty_discount_amount ?? 0);
    }

    protected function getOrderStampDiscount(\App\Models\Order $order): float
    {
        return (float)($order->stamp_discount_amount ?? 0);
    }

    protected function appendLoyaltyFieldsToOrderUpdate(\App\Models\Order $order, array &$updateData): void
    {
        if ($order->loyalty_discount_amount !== null) {
            $updateData['loyalty_discount_amount'] = round((float) $order->loyalty_discount_amount, 2);
        }
        if ($order->stamp_discount_amount !== null) {
            $updateData['stamp_discount_amount'] = round((float) $order->stamp_discount_amount, 2);
        }
        if ($order->loyalty_points_redeemed !== null) {
            $updateData['loyalty_points_redeemed'] = (int) $order->loyalty_points_redeemed;
        }
    }

    private static $processedStampRules = [];

    private function redeemStampsForAllEligibleItems(Order $order, int $stampRuleId): void
    {
        if (!module_enabled('Loyalty')) {
            return;
        }

        // CRITICAL: Prevent duplicate calls for the same stamp rule in the same request
        $cacheKey = $order->id . '_' . $stampRuleId;
        if (isset(self::$processedStampRules[$cacheKey])) {
            \Illuminate\Support\Facades\Log::warning('redeemStampsForAllEligibleItems - already processed in this request', [
                'order_id' => $order->id,
                'stamp_rule_id' => $stampRuleId,
            ]);
            return;
        }

        try {
            // CRITICAL: Use database lock to prevent concurrent redemption
            \Illuminate\Support\Facades\DB::transaction(function () use ($order, $stampRuleId, $cacheKey) {
                // Refresh order to get latest data
                $order->refresh();
                $order->load('items', 'branch');

                // CRITICAL: Check customer stamp balance BEFORE redemption
                // This prevents duplicate deductions
                $restaurantId = $order->branch->restaurant_id ?? restaurant()->id ?? null;
                $customerId = $order->customer_id;

                if (!$restaurantId) {
                    \Illuminate\Support\Facades\Log::error('redeemStampsForAllEligibleItems - restaurant_id is null', [
                        'order_id' => $order->id,
                        'stamp_rule_id' => $stampRuleId,
                        'branch_id' => $order->branch_id,
                    ]);
                    return;
                }

                if (module_enabled('Loyalty')) {
                    $customerStamp = \Modules\Loyalty\Entities\CustomerStamp::getOrCreate(
                        $restaurantId,
                        $customerId,
                        $stampRuleId
                    );

                    // Get current stamp balance
                    $stampsBeforeRedemption = $customerStamp->stamps_earned - $customerStamp->stamps_redeemed;

                    // Get stamp rule to know how many stamps are required
                    $stampRule = null;
                    if (module_enabled('Loyalty')) {
                        $stampRule = \Modules\Loyalty\Entities\LoyaltyStampRule::find($stampRuleId);
                    }

                    $stampsRequired = $stampRule ? ($stampRule->stamps_required ?? 1) : 1;

                    // Count how many items are eligible for redemption
                    // This includes both discounted items AND free items
                    // Free items also require stamp deduction

                    // Count free items with this stamp_rule_id (they always need redemption)
                    $freeItemsCount = $order->items()
                        ->where('stamp_rule_id', $stampRuleId)
                        ->where('is_free_item_from_stamp', true)
                        ->count();

                    // Count discounted items (items with stamp_rule_id but not free)
                    // CRITICAL: Also check if discount was already applied to item amount
                    $eligibleItems = $order->items()
                        ->where('stamp_rule_id', $stampRuleId)
                        ->where(function ($q) {
                            $q->whereNull('is_free_item_from_stamp')
                                ->orWhere('is_free_item_from_stamp', false);
                        })
                        ->get();

                    // Filter out items where discount was already applied (amount is less than price * quantity)
                    // If discount was already applied in POS, the amount will be less than expected
                    $itemsNeedingRedemption = $eligibleItems->filter(function ($item) {
                        // Calculate expected original amount (price * quantity)
                        $basePrice = (float)($item->price ?? 0);
                        $quantity = (int)($item->quantity ?? 1);
                        $expectedOriginalAmount = $basePrice * $quantity;
                        $currentAmount = (float)($item->amount ?? 0);

                        // If current amount is significantly less than expected, discount was already applied
                        // Allow small rounding differences (0.01) but if difference is larger, discount was applied
                        $difference = $expectedOriginalAmount - $currentAmount;

                        // If difference is very small (< 0.01), discount was NOT applied yet
                        // If difference is larger, discount was already applied, so skip this item
                        return $difference < 0.01;
                    });

                    // Total eligible items = free items + discounted items needing redemption
                    $eligibleItemsCount = $freeItemsCount + $itemsNeedingRedemption->count();

                    // Count existing transactions for this stamp rule and order
                    $existingTransactionsCount = 0;
                    if (module_enabled('Loyalty')) {
                        $existingTransactionsCount = \Modules\Loyalty\Entities\LoyaltyStampTransaction::where('order_id', $order->id)
                            ->where('stamp_rule_id', $stampRuleId)
                            ->where('type', 'REDEEM')
                            ->lockForUpdate() // Lock to prevent concurrent access
                            ->count();
                    }

                    // Calculate how many items should be redeemed
                    $itemsToRedeem = $eligibleItemsCount;
                    $totalStampsNeeded = $itemsToRedeem * $stampsRequired;

                    \Illuminate\Support\Facades\Log::info('redeemStampsForAllEligibleItems - pre-redemption check', [
                        'order_id' => $order->id,
                        'stamp_rule_id' => $stampRuleId,
                        'customer_id' => $customerId,
                        'stamps_before' => $stampsBeforeRedemption,
                        'stamps_required_per_item' => $stampsRequired,
                        'eligible_items_count' => $eligibleItemsCount,
                        'existing_transactions' => $existingTransactionsCount,
                        'items_to_redeem' => $itemsToRedeem,
                        'total_stamps_needed' => $totalStampsNeeded,
                    ]);

                    // CRITICAL: Only proceed if:
                    // 1. There are eligible items
                    // 2. Customer has enough stamps
                    // 3. Transactions don't already match eligible items (prevent duplicate)
                    if ($eligibleItemsCount <= 0) {
                        \Illuminate\Support\Facades\Log::info('redeemStampsForAllEligibleItems - no eligible items', [
                            'order_id' => $order->id,
                            'stamp_rule_id' => $stampRuleId,
                        ]);
                        return;
                    }

                    if ($stampsBeforeRedemption < $stampsRequired) {
                        \Illuminate\Support\Facades\Log::warning('redeemStampsForAllEligibleItems - insufficient stamps', [
                            'order_id' => $order->id,
                            'stamp_rule_id' => $stampRuleId,
                            'stamps_available' => $stampsBeforeRedemption,
                            'stamps_required' => $stampsRequired,
                        ]);
                        return;
                    }

                    // CRITICAL: If transactions already exist for all eligible items, skip redemption
                    // This prevents duplicate redemption
                    if ($existingTransactionsCount >= $eligibleItemsCount) {
                        \Illuminate\Support\Facades\Log::info('redeemStampsForAllEligibleItems - all items already redeemed', [
                            'order_id' => $order->id,
                            'stamp_rule_id' => $stampRuleId,
                            'existing_transactions' => $existingTransactionsCount,
                            'eligible_items' => $eligibleItemsCount,
                        ]);
                        return;
                    }

                    // CRITICAL: Call the service ONCE per stamp rule
                    // The service now handles redeeming all eligible items in one call
                    $loyaltyService = app(\Modules\Loyalty\Services\LoyaltyService::class);
                    $result = $loyaltyService->redeemStamps($order, $stampRuleId);

                    if (!is_array($result) || !($result['success'] ?? false)) {
                        \Illuminate\Support\Facades\Log::warning('redeemStampsForAllEligibleItems - service returned failure', [
                            'order_id' => $order->id,
                            'stamp_rule_id' => $stampRuleId,
                            'result' => $result,
                        ]);
                        return;
                    }

                    // Refresh to get updated customer stamp balance
                    $customerStamp->refresh();
                    $stampsAfterRedemption = $customerStamp->stamps_earned - $customerStamp->stamps_redeemed;
                    $stampsDeducted = $stampsBeforeRedemption - $stampsAfterRedemption;

                    // Refresh order to get updated items
                    $order->refresh();
                    $order->load('items');

                    // Count transactions after redemption
                    $afterTransactionCount = 0;
                    if (module_enabled('Loyalty')) {
                        $afterTransactionCount = \Modules\Loyalty\Entities\LoyaltyStampTransaction::where('order_id', $order->id)
                            ->where('stamp_rule_id', $stampRuleId)
                            ->where('type', 'REDEEM')
                            ->count();
                    }

                    \Illuminate\Support\Facades\Log::info('redeemStampsForAllEligibleItems - redemption completed', [
                        'order_id' => $order->id,
                        'stamp_rule_id' => $stampRuleId,
                        'stamps_before' => $stampsBeforeRedemption,
                        'stamps_after' => $stampsAfterRedemption,
                        'stamps_deducted' => $stampsDeducted,
                        'transactions_before' => $existingTransactionsCount,
                        'transactions_after' => $afterTransactionCount,
                        'transactions_created' => $afterTransactionCount - $existingTransactionsCount,
                        'expected_stamps_deducted' => ($afterTransactionCount - $existingTransactionsCount) * $stampsRequired,
                    ]);

                    // CRITICAL: Verify stamps were deducted correctly
                    // If stamps were deducted but no transactions created, or vice versa, log warning
                    $expectedStampsDeducted = ($afterTransactionCount - $existingTransactionsCount) * $stampsRequired;
                    if ($stampsDeducted != $expectedStampsDeducted && $expectedStampsDeducted > 0) {
                        \Illuminate\Support\Facades\Log::warning('redeemStampsForAllEligibleItems - stamp deduction mismatch', [
                            'order_id' => $order->id,
                            'stamp_rule_id' => $stampRuleId,
                            'stamps_deducted' => $stampsDeducted,
                            'expected_stamps_deducted' => $expectedStampsDeducted,
                            'transactions_created' => $afterTransactionCount - $existingTransactionsCount,
                        ]);
                    }

                    // Mark as processed to prevent duplicate calls
                    self::$processedStampRules[$cacheKey] = true;
                }
            });
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('redeemStampsForAllEligibleItems - failed', [
                'order_id' => $order->id ?? null,
                'stamp_rule_id' => $stampRuleId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e; // Re-throw to ensure transaction rollback
        }
    }
}
