<?php

namespace Modules\Loyalty\Traits;

use Modules\Loyalty\Services\LoyaltyService;
use Modules\Loyalty\Entities\LoyaltySetting;
use Modules\Loyalty\Entities\LoyaltyTier;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

trait HasLoyaltyIntegration
{
    // Loyalty properties
    public $loyaltyPointsRedeemed = 0;
    public $loyaltyDiscountAmount = 0;
    public $showLoyaltyRedemptionModal = false;
    public $availableLoyaltyPoints = 0;
    public $maxLoyaltyDiscount = 0;
    public $loyaltyPointsValue = 0;
    public $pointsToRedeem = 0;
    public $maxRedeemablePoints = 0; // Maximum points that can be redeemed (multiple of min_redeem_points)
    public $minRedeemPoints = 0; // Minimum points required to redeem
    // Tier information
    public $currentTier = null;
    public $nextTier = null;
    public $pointsToNextTier = null;
    public $tierProgress = 0;

    /**
     * Reset loyalty redemption when customer changes
     */
    public function resetLoyaltyRedemption()
    {
        $this->loyaltyPointsRedeemed = 0;
        $this->loyaltyDiscountAmount = 0;
        if (method_exists($this, 'calculateTotal')) {
            $this->calculateTotal();
        }
    }

    /**
     * Apply loyalty redemption from external event
     */
    #[On('loyaltyPointsRedeemed')]
    public function applyLoyaltyRedemption($points, $discountAmount)
    {
        $this->loyaltyPointsRedeemed = $points;
        $this->loyaltyDiscountAmount = $discountAmount;
        if (method_exists($this, 'calculateTotal')) {
            $this->calculateTotal();
        }
    }

    /**
     * Remove loyalty redemption
     */
    #[On('loyaltyRedemptionRemoved')]
    public function removeLoyaltyRedemption()
    {
        // Check if this is a shop/order detail context (has order property)
        if (property_exists($this, 'order') && $this->order) {
            // Handle shop order removal with full recalculation
            return $this->removeLoyaltyRedemptionFromShopOrder();
        }
        
        // Default behavior for POS/cart contexts
        $this->loyaltyPointsRedeemed = 0;
        $this->loyaltyDiscountAmount = 0;
        if (method_exists($this, 'calculateTotal')) {
            $this->calculateTotal();
        }
    }
    
    /**
     * Remove loyalty redemption from shop order with full order recalculation
     * This method handles the complete removal including order total recalculation
     */
    protected function removeLoyaltyRedemptionFromShopOrder()
    {
        if (!property_exists($this, 'order') || !$this->order) {
            return false;
        }
        
        // Remove redemption from database
        $success = $this->removeLoyaltyRedemptionFromOrder($this->order);
        
        if (!$success) {
            // Show error message if available
            if (method_exists($this, 'alert')) {
                $this->alert('error', __('loyalty::app.redemptionRemoveFailed'), [
                    'toast' => true,
                    'position' => 'top-end',
                ]);
            }
            return false;
        }
        
        // Reload order
        $this->order->refresh();
        $this->order->load(['taxes.tax', 'charges.charge', 'items']);
        
        // Recalculate order total without loyalty discount
        $this->recalculateOrderTotalAfterLoyaltyRemoval();
        
        // Reload loyalty data if restaurant and customer properties exist
        if (property_exists($this, 'restaurant') && property_exists($this, 'customer') && $this->restaurant && $this->customer) {
            $this->loadLoyaltyDataForOrder($this->order, $this->restaurant->id, $this->customer->id, $this->order->sub_total);
        }
        
        // Show success message if available
        if (method_exists($this, 'alert')) {
            $this->alert('success', __('loyalty::app.redemptionRemoved'), [
                'toast' => true,
                'position' => 'top-end',
            ]);
        }
        
        return true;
    }
    
    /**
     * Recalculate order total after loyalty redemption removal
     * This method recalculates the order total without the loyalty discount
     */
    protected function recalculateOrderTotalAfterLoyaltyRemoval()
    {
        if (!property_exists($this, 'order') || !$this->order) {
            return;
        }
        
        // Start fresh from item amounts to ensure correct calculation
        // Ensure float precision for all calculations
        $correctSubTotal = (float)($this->order->items->sum('amount') ?? 0);
        $correctTotal = (float)$correctSubTotal;
        
        // Apply regular discount (ensure float)
        $correctTotal -= (float)($this->order->discount_amount ?? 0);
        
        // Calculate taxes on subtotal (tax base is subtotal after regular discount)
        $taxBase = (float)$correctTotal; // Subtotal after regular discount
        $correctTaxAmount = 0.0;
        
        if ($this->order->tax_mode === 'order' && $this->order->taxes && $this->order->taxes->count() > 0) {
            // Order-level taxes - calculate on tax base
            // IMPORTANT: Don't round individual tax amounts, only round the final sum
            foreach ($this->order->taxes as $orderTax) {
                $tax = $orderTax->tax ?? null;
                if ($tax && isset($tax->tax_percent)) {
                    $taxPercent = (float)$tax->tax_percent;
                    // Calculate tax amount on base
                    $taxAmount = ($taxPercent / 100.0) * (float)$taxBase;
                    // Add to running total with full precision
                    $correctTaxAmount += $taxAmount;
                }
            }
            // Round ONLY the final sum to 2 decimal places
            $correctTaxAmount = round($correctTaxAmount, 2);
        } else {
            // Item-level taxes - sum from order items (already calculated with precision)
            $correctTaxAmount = (float)($this->order->items->sum('tax_amount') ?? 0);
        }
        
        // Add taxes to total (for order-level, always add; for item-level, add if exclusive)
        if ($this->order->tax_mode === 'order') {
            // Order-level taxes are always exclusive, so add them
            $correctTotal += (float)$correctTaxAmount;
        } else {
            // Item-level taxes - check if restaurant has tax_inclusive setting
            $isInclusive = false;
            if (property_exists($this, 'restaurant') && $this->restaurant) {
                $isInclusive = ($this->restaurant->tax_inclusive ?? false);
            }
            
            if (!$isInclusive && $correctTaxAmount > 0) {
                // For exclusive taxes, add to total
                $correctTotal += (float)$correctTaxAmount;
            }
            // For inclusive taxes, tax is already included in item prices (amount field)
            // So we don't add it to total, but we still track it for total_tax_amount
        }
        
        // Apply extra charges (on discounted base) - ensure float precision
        // Base for charges is subtotal after regular discount (before loyalty discount)
        $chargeBase = (float)$correctSubTotal - (float)($this->order->discount_amount ?? 0);
        if ($this->order->charges && $this->order->charges->count() > 0) {
            foreach ($this->order->charges as $chargeRelation) {
                $charge = $chargeRelation->charge;
                if ($charge) {
                    $chargeAmount = $charge->getAmount((float)$chargeBase);
                    $correctTotal += (float)$chargeAmount;
                }
            }
        }
        
        // Add tip and delivery (ensure float)
        $correctTotal += (float)($this->order->tip_amount ?? 0);
        $correctTotal += (float)($this->order->delivery_fee ?? 0);
        
        // Round final values to 2 decimal places
        $correctTotal = round($correctTotal, 2);
        $correctTaxAmount = round($correctTaxAmount, 2);
        
        // FORCE UPDATE total and tax_amount - this is critical!
        \Illuminate\Support\Facades\DB::table('orders')->where('id', $this->order->id)->update([
            'total' => $correctTotal,
            'total_tax_amount' => $correctTaxAmount,
        ]);
        
        $this->order->refresh();
        
        // Update component total if property exists
        if (property_exists($this, 'total')) {
            $this->total = floatval($this->order->total) - floatval($this->order->amount_paid ?: 0);
        }
    }

    /**
     * Check if loyalty points are available when customer is selected
     */
    protected function checkLoyaltyPointsOnCustomerSelect()
    {
        // Check if module is enabled
        if (!$this->isLoyaltyEnabled()) {
            return;
        }

        try {
            $loyaltyService = app(LoyaltyService::class);
            $restaurantId = restaurant()->id;
            
            // Ensure customerId is available
            if (!$this->customerId) {
                return;
            }
            
            // Get available points
            $this->availableLoyaltyPoints = $loyaltyService->getAvailablePoints($restaurantId, $this->customerId);
            
            if ($this->availableLoyaltyPoints > 0) {
                // Get settings for value per point
                $settings = LoyaltySetting::getForRestaurant($restaurantId);
                if ($settings && $settings->isEnabled()) {
                    // Store minimum redeem points
                    $this->minRedeemPoints = $settings->min_redeem_points ?? 0;
                    
                    // Update loyalty values based on current subtotal (this calculates maxRedeemablePoints considering max discount)
                    $this->updateLoyaltyValues();
                    
                    // Default to minimum redeem points if available, otherwise max redeemable
                    $this->pointsToRedeem = $this->minRedeemPoints > 0 ? $this->minRedeemPoints : ($this->maxRedeemablePoints > 0 ? $this->maxRedeemablePoints : 0);
                    
                    // Only show modal if customer has enough points to meet minimum
                    if ($this->availableLoyaltyPoints >= $this->minRedeemPoints) {
                        $this->showLoyaltyRedemptionModal = true;
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to check loyalty points on customer select: ' . $e->getMessage());
        }
    }

    /**
     * Update loyalty values when subtotal changes
     */
    protected function updateLoyaltyValues()
    {
        if (!$this->isLoyaltyEnabled() || !$this->customerId) {
            return;
        }

        try {
            $loyaltyService = app(LoyaltyService::class);
            $restaurantId = restaurant()->id;
            $settings = LoyaltySetting::getForRestaurant($restaurantId);
            
            if ($settings && $settings->isEnabled()) {
                // Store minimum redeem points
                $this->minRedeemPoints = $settings->min_redeem_points ?? 0;
                
                // Calculate max discount (use current subtotal or 0)
                $subtotal = $this->subTotal ?? 0;
                $maxDiscountData = $loyaltyService->calculateMaxDiscount($restaurantId, $this->customerId, $subtotal);
                
                // CRITICAL: Calculate actual max discount TODAY (percentage of subtotal)
                // This is the maximum discount allowed today based on max_discount_percent setting
                // NOT the capped value from calculateMaxDiscount (which considers available points)
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
                    $maxPointsFromDiscountValue = floor($maxDiscountToday / $settings->value_per_point);
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
                
                // Calculate points value
                $this->loyaltyPointsValue = $this->availableLoyaltyPoints * $settings->value_per_point;
                
                // If points are already redeemed, recalculate discount based on current subtotal
                if ($this->loyaltyPointsRedeemed > 0) {
                    $maxDiscount = $maxDiscountData['max_discount'] ?? 0;
                    $discount = min($this->loyaltyPointsRedeemed * $settings->value_per_point, $maxDiscount);
                    $this->loyaltyDiscountAmount = $discount;
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to update loyalty values: ' . $e->getMessage());
        }
    }

    /**
     * Check if loyalty points are available and show redemption modal (legacy method for backward compatibility)
     */
    protected function checkLoyaltyPoints($action, $secondAction = null, $thirdAction = null)
    {
        // This method is kept for backward compatibility but now checks on customer select
        // Just proceed with order since loyalty check happens on customer select
        if (method_exists($this, 'executeSaveOrder')) {
            $this->executeSaveOrder($action, $secondAction, $thirdAction);
        } elseif (method_exists($this, 'saveOrder')) {
            $this->saveOrder($action, $secondAction, $thirdAction);
        }
    }

    /**
     * Handle order proceeding after loyalty check
     */
    public function proceedWithOrder($action = null, $secondAction = null, $thirdAction = null)
    {
        // Use stored action if provided, otherwise use parameters
        if (isset($this->intendedOrderAction) && is_array($this->intendedOrderAction)) {
            // Check if we have loyalty actions stored
            if (isset($this->intendedOrderAction['loyaltyAction'])) {
                $action = $this->intendedOrderAction['loyaltyAction'] ?? $action;
                $secondAction = $this->intendedOrderAction['loyaltySecondAction'] ?? $secondAction;
                $thirdAction = $this->intendedOrderAction['loyaltyThirdAction'] ?? $thirdAction;
                // Remove loyalty actions but keep reservation action if exists
                unset(
                    $this->intendedOrderAction['loyaltyAction'],
                    $this->intendedOrderAction['loyaltySecondAction'],
                    $this->intendedOrderAction['loyaltyThirdAction']
                );
            } else {
                // Regular action
                $action = $this->intendedOrderAction['action'] ?? $action;
                $secondAction = $this->intendedOrderAction['secondAction'] ?? $secondAction;
                $thirdAction = $this->intendedOrderAction['thirdAction'] ?? $thirdAction;
                // Only clear if no reservation action exists
                if (!isset($this->intendedOrderAction['reservationAction'])) {
                    $this->intendedOrderAction = null;
                }
            }
        }
        
        $this->showLoyaltyRedemptionModal = false;
        
        // Call the actual saveOrder logic
        if (method_exists($this, 'executeSaveOrder')) {
            $this->executeSaveOrder($action, $secondAction, $thirdAction);
        } elseif (method_exists($this, 'saveOrder')) {
            $this->saveOrder($action, $secondAction, $thirdAction);
        }
    }

    /**
     * Redeem loyalty points (applies discount without proceeding with order)
     */
    public function redeemLoyaltyPoints($points = null)
    {
        if (!$this->customerId) {
            return;
        }
        
        try {
            $loyaltyService = app(LoyaltyService::class);
            $restaurantId = restaurant()->id;
            
            // Get settings first (needed for min_redeem_points)
            $settings = LoyaltySetting::getForRestaurant($restaurantId);
            if (!$settings || !$settings->isEnabled()) {
                $this->showLoyaltyRedemptionModal = false;
                return;
            }
            
            $minRedeemPoints = $settings->min_redeem_points ?? 0;
            
            // Use specified points, or pointsToRedeem from input, or default to minimum
            // CRITICAL: If points parameter is null/0, use the component's pointsToRedeem property (from input field)
            if ($points !== null && $points > 0) {
                $pointsToRedeem = (int) $points;
            } elseif ($this->pointsToRedeem > 0) {
                $pointsToRedeem = (int) $this->pointsToRedeem;
            } else {
                // Fallback: use minimum redeem points if available
                $pointsToRedeem = $minRedeemPoints > 0 ? $minRedeemPoints : 0;
            }
            
            // CRITICAL: Validate minimum points and ensure it's a multiple of min_redeem_points
            if ($minRedeemPoints > 0) {
                if ($pointsToRedeem < $minRedeemPoints) {
                    $this->alert('error', __('Minimum :min points required for redemption', ['min' => $minRedeemPoints]), [
                        'toast' => true,
                        'position' => 'top-end',
                    ]);
                    return;
                }
                
                // Ensure points are a multiple of min_redeem_points
                if ($pointsToRedeem % $minRedeemPoints !== 0) {
                    // Round down to nearest multiple
                    $pointsToRedeem = floor($pointsToRedeem / $minRedeemPoints) * $minRedeemPoints;
                    if ($pointsToRedeem < $minRedeemPoints) {
                        $this->alert('error', __('Points must be in multiples of :min', ['min' => $minRedeemPoints]), [
                            'toast' => true,
                            'position' => 'top-end',
                        ]);
                        return;
                    }
                }
            }
            
            // Calculate discount (use current subtotal or 0)
            // Note: If subtotal is 0, discount will be recalculated in calculateTotal() based on actual subtotal
            $subtotal = $this->subTotal ?? 0;
            
            // CRITICAL: First, ensure maxRedeemablePoints is up-to-date
            // Recalculate if not set or if subtotal changed
            if ($this->maxRedeemablePoints == 0 || $subtotal > 0) {
                $this->updateLoyaltyValues();
            }
            
            // Get max discount for TODAY (based on max_discount_percent of subtotal)
            // This is the maximum discount allowed today, not per redemption
            $maxDiscountToday = 0;
            if ($subtotal > 0) {
                $maxDiscountData = $loyaltyService->calculateMaxDiscount($restaurantId, $this->customerId, $subtotal);
                // Get the actual max discount (percentage of subtotal), not the capped value
                $settings = LoyaltySetting::getForRestaurant($restaurantId);
                if ($settings && $settings->isEnabled()) {
                    $maxDiscountToday = $subtotal * ($settings->max_discount_percent / 100);
                }
            }
            
            // CRITICAL: Cap points at maxRedeemablePoints FIRST (this already considers max discount)
            // This ensures consistency with what's shown in the UI
            if ($this->maxRedeemablePoints > 0 && $pointsToRedeem > $this->maxRedeemablePoints) {
                $pointsToRedeem = $this->maxRedeemablePoints;
                
                if (method_exists($this, 'alert')) {
                    $this->alert('warning', __('Maximum redeemable points is :max. Adjusted to :points points.', [
                        'max' => number_format($this->maxRedeemablePoints),
                        'points' => number_format($pointsToRedeem)
                    ]), [
                        'toast' => true,
                        'position' => 'top-end',
                    ]);
                }
            }
            
            // Also verify discount doesn't exceed max (double-check)
            $requestedDiscount = $pointsToRedeem * $settings->value_per_point;
            if ($maxDiscountToday > 0 && $requestedDiscount > $maxDiscountToday) {
                // Calculate maximum points from discount
                $rawMaxPoints = floor($maxDiscountToday / $settings->value_per_point);
                
                // Ensure it's a multiple of min_redeem_points
                $maxPointsForDiscount = $rawMaxPoints;
                if ($minRedeemPoints > 0 && $maxPointsForDiscount >= $minRedeemPoints) {
                    $maxPointsForDiscount = floor($maxPointsForDiscount / $minRedeemPoints) * $minRedeemPoints;
                }
                
                // Ensure we don't go below minimum
                if ($maxPointsForDiscount < $minRedeemPoints) {
                    if ($rawMaxPoints >= $minRedeemPoints) {
                        $maxPointsForDiscount = $minRedeemPoints;
                    } else {
                        if (method_exists($this, 'alert')) {
                            $this->alert('error', __('Maximum discount today is :max. You cannot redeem more points.', ['max' => currency_format($maxDiscountToday, restaurant()->currency_id)]), [
                                'toast' => true,
                                'position' => 'top-end',
                            ]);
                        }
                        return;
                    }
                }
                
                // Use the smaller of the two constraints
                $pointsToRedeem = min($pointsToRedeem, $maxPointsForDiscount);
                
                if (method_exists($this, 'alert')) {
                    $this->alert('warning', __('Maximum discount today is :max. Adjusted to :points points.', [
                        'max' => currency_format($maxDiscountToday, restaurant()->currency_id),
                        'points' => number_format($pointsToRedeem)
                    ]), [
                        'toast' => true,
                        'position' => 'top-end',
                    ]);
                }
            }
            
            // Check available points AFTER max discount adjustment
            $availablePoints = $loyaltyService->getAvailablePoints($restaurantId, $this->customerId);
            if ($pointsToRedeem > $availablePoints) {
                // If after max discount adjustment, still exceeds available, use available points (if it meets minimum)
                if ($availablePoints >= $minRedeemPoints) {
                    // Ensure available points is a multiple of min_redeem_points
                    $availablePointsAdjusted = floor($availablePoints / $minRedeemPoints) * $minRedeemPoints;
                    if ($availablePointsAdjusted >= $minRedeemPoints) {
                        $pointsToRedeem = $availablePointsAdjusted;
                        if (method_exists($this, 'alert')) {
                            $this->alert('warning', __('You only have :points points available. Adjusted to :points points.', [
                                'points' => number_format($pointsToRedeem)
                            ]), [
                                'toast' => true,
                                'position' => 'top-end',
                            ]);
                        }
                    } else {
                        if (method_exists($this, 'alert')) {
                            $this->alert('error', __('loyalty::app.insufficientPoints'), [
                                'toast' => true,
                                'position' => 'top-end',
                            ]);
                        }
                        $this->showLoyaltyRedemptionModal = false;
                        return;
                    }
                } else {
                    if (method_exists($this, 'alert')) {
                        $this->alert('error', __('loyalty::app.insufficientPoints'), [
                            'toast' => true,
                            'position' => 'top-end',
                        ]);
                    }
                    $this->showLoyaltyRedemptionModal = false;
                    return;
                }
            }
            
            // Calculate discount based on the FINAL adjusted pointsToRedeem value
            // Don't recalculate points from discount - use the adjusted pointsToRedeem directly
            // Calculate points discount first
            $pointsDiscount = $pointsToRedeem * $settings->value_per_point;
            
            // Cap at max discount TODAY only if subtotal > 0
            // maxDiscountToday is the maximum discount allowed today (percentage of subtotal)
            // If subtotal is 0, use points discount directly (max discount calculation would be wrong)
            $discount = ($maxDiscountToday > 0) ? min($pointsDiscount, $maxDiscountToday) : $pointsDiscount;
            
            // Ensure points meet minimum after all adjustments
            if ($pointsToRedeem < $settings->min_redeem_points) {
                if (method_exists($this, 'alert')) {
                    $this->alert('error', __('loyalty::app.minPointsRequired', ['min_points' => $settings->min_redeem_points]), [
                        'toast' => true,
                        'position' => 'top-end',
                    ]);
                }
                $this->showLoyaltyRedemptionModal = false;
                return;
            }
            
            // Set redeemed points and discount amount - use pointsToRedeem directly (already adjusted for max discount)
            $this->loyaltyPointsRedeemed = $pointsToRedeem;
            $this->loyaltyDiscountAmount = $discount;
            
            // Close modal
            $this->showLoyaltyRedemptionModal = false;
            
            // Recalculate total to apply discount
            // This will recalculate the discount based on actual subtotal if it was 0 before
            if (method_exists($this, 'calculateTotal')) {
                $this->calculateTotal();
            }
            
            // Show success message
            if (method_exists($this, 'alert')) {
                $this->alert('success', __('loyalty::app.pointsRedeemedSuccessfully'), [
                    'toast' => true,
                    'position' => 'top-end',
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to redeem loyalty points: ' . $e->getMessage());
            if (method_exists($this, 'alert')) {
                $this->alert('error', __('loyalty::app.failedToRedeemPoints'), [
                    'toast' => true,
                    'position' => 'top-end',
                ]);
            }
        }
    }

    /**
     * Skip loyalty redemption (just close modal, don't proceed with order)
     */
    public function skipLoyaltyRedemption()
    {
        $this->showLoyaltyRedemptionModal = false;
    }

    /**
     * Edit loyalty redemption - reopen modal with current redemption values
     */
    public function editLoyaltyRedemption()
    {
        if (!$this->isLoyaltyEnabled() || !$this->customerId) {
            return;
        }

        try {
            // Set pointsToRedeem to current redeemed points
            $this->pointsToRedeem = $this->loyaltyPointsRedeemed > 0 ? $this->loyaltyPointsRedeemed : $this->minRedeemPoints;
            
            // Update loyalty values to ensure maxRedeemablePoints is current
            $this->updateLoyaltyValues();
            
            // Show the modal
            $this->showLoyaltyRedemptionModal = true;
        } catch (\Exception $e) {
            Log::error('Failed to edit loyalty redemption: ' . $e->getMessage());
        }
    }

    /**
     * Check if loyalty module is enabled for the restaurant
     * @param string|null $platform Platform to check: 'pos', 'customer_site', 'kiosk', or null for all
     */
    protected function isLoyaltyEnabled(?string $platform = null): bool
    {
        if (!module_enabled('Loyalty')) {
            return false;
        }

        if (!function_exists('restaurant_modules')) {
            return false;
        }

        $restaurantModules = restaurant_modules();
        if (!in_array('Loyalty', $restaurantModules)) {
            return false;
        }

        // If no platform specified, just check if module is enabled
        if ($platform === null) {
            return true;
        }

        // Check platform-specific setting
        try {
            $restaurantId = function_exists('restaurant') ? restaurant()->id : null;
            if (!$restaurantId) {
                return false;
            }

            $settings = LoyaltySetting::getForRestaurant($restaurantId);
            
            if (!$settings->enabled) {
                return false;
            }

            // Check platform-specific enablement for points and stamps separately
            // Check if the new platform fields exist in the database (after migration)
            $hasPointsPlatformFields = Schema::hasColumn('loyalty_settings', 'enable_points_for_pos');
            $hasStampsPlatformFields = Schema::hasColumn('loyalty_settings', 'enable_stamps_for_pos');
            
            switch ($platform) {
                case 'pos':
                    if ($hasPointsPlatformFields && $hasStampsPlatformFields) {
                        $pointsEnabled = $settings->enable_points && ($settings->enable_points_for_pos === true);
                        $stampsEnabled = $settings->enable_stamps && ($settings->enable_stamps_for_pos === true);
                    } else {
                        $pointsEnabled = $settings->enable_points && ($settings->enable_for_pos ?? true);
                        $stampsEnabled = $settings->enable_stamps && ($settings->enable_for_pos ?? true);
                    }
                    return $pointsEnabled || $stampsEnabled;
                case 'customer_site':
                    if ($hasPointsPlatformFields && $hasStampsPlatformFields) {
                        $pointsEnabled = $settings->enable_points && ($settings->enable_points_for_customer_site === true);
                        $stampsEnabled = $settings->enable_stamps && ($settings->enable_stamps_for_customer_site === true);
                    } else {
                        $pointsEnabled = $settings->enable_points && ($settings->enable_for_customer_site ?? true);
                        $stampsEnabled = $settings->enable_stamps && ($settings->enable_for_customer_site ?? true);
                    }
                    return $pointsEnabled || $stampsEnabled;
                case 'kiosk':
                    if ($hasPointsPlatformFields && $hasStampsPlatformFields) {
                        $pointsEnabled = $settings->enable_points && ($settings->enable_points_for_kiosk === true);
                        $stampsEnabled = $settings->enable_stamps && ($settings->enable_stamps_for_kiosk === true);
                    } else {
                        $pointsEnabled = $settings->enable_points && ($settings->enable_for_kiosk ?? true);
                        $stampsEnabled = $settings->enable_stamps && ($settings->enable_for_kiosk ?? true);
                    }
                    return $pointsEnabled || $stampsEnabled;
                default:
                    return true;
            }
        } catch (\Exception $e) {
            Log::error('Error checking loyalty platform settings: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get loyalty data for order saving
     */
    protected function getLoyaltyOrderData(): array
    {
        return [
            'loyalty_points_redeemed' => $this->loyaltyPointsRedeemed ?? 0,
            'loyalty_discount_amount' => $this->loyaltyDiscountAmount ?? 0,
        ];
    }

    /**
     * Apply loyalty discount to total calculation
     */
    protected function applyLoyaltyDiscountToTotal(&$total, $order = null)
    {
        // Check for loyalty discount first (loyalty points cannot be combined with other discounts)
        $loyaltyDiscountAmount = 0;
        
        if ($this->loyaltyPointsRedeemed > 0) {
            $loyaltyDiscountAmount = $this->loyaltyDiscountAmount ?? 0;
        } elseif ($order && $order->loyalty_points_redeemed > 0) {
            $loyaltyDiscountAmount = $order->loyalty_discount_amount ?? 0;
            $this->loyaltyPointsRedeemed = $order->loyalty_points_redeemed;
            $this->loyaltyDiscountAmount = $loyaltyDiscountAmount;
        }

        // Apply discount if we have a valid amount
        if ($loyaltyDiscountAmount > 0) {
            if (property_exists($this, 'discountAmount')) {
                $this->discountAmount = $loyaltyDiscountAmount;
            }
            // Subtract the discount from total
            $total = $total - $loyaltyDiscountAmount;
        }
    }

    /**
     * Calculate total discount including loyalty discount (for payment calculations)
     * This is a static helper that can be used without the trait
     */
    public static function calculateTotalDiscount($order): float
    {
        $regularDiscount = floatval($order->discount_amount ?? 0);
        $loyaltyDiscount = floatval($order->loyalty_discount_amount ?? 0);
        return $regularDiscount + $loyaltyDiscount;
    }

    /**
     * Get loyalty discount amount from order (helper for payment calculations)
     */
    public static function getLoyaltyDiscountFromOrder($order): float
    {
        return floatval($order->loyalty_discount_amount ?? 0);
    }

    /**
     * Check if order has loyalty discount
     */
    public static function hasLoyaltyDiscount($order): bool
    {
        return ($order->loyalty_points_redeemed ?? 0) > 0 && ($order->loyalty_discount_amount ?? 0) > 0;
    }

    /**
     * Load loyalty data for an existing order (for shop/order detail pages)
     * This method loads available points and calculates max discount for an order
     */
    public function loadLoyaltyDataForOrder($order, $restaurantId, $customerId, $subTotal = null)
    {
        if (!$this->isLoyaltyEnabled() || !$customerId) {
            return;
        }

        try {
            $loyaltyService = app(\Modules\Loyalty\Services\LoyaltyService::class);
            
            $this->availableLoyaltyPoints = $loyaltyService->getAvailablePoints($restaurantId, $customerId);
            
            if ($this->availableLoyaltyPoints > 0) {
                $settings = \Modules\Loyalty\Entities\LoyaltySetting::getForRestaurant($restaurantId);
                if ($settings && $settings->isEnabled()) {
                    $this->loyaltyPointsValue = $this->availableLoyaltyPoints * $settings->value_per_point;
                    
                    $orderSubTotal = $subTotal ?? $order->sub_total ?? 0;
                    $maxDiscountData = $loyaltyService->calculateMaxDiscount($restaurantId, $customerId, $orderSubTotal);
                    $this->maxLoyaltyDiscount = $maxDiscountData['max_discount'] ?? 0;
                    
                    // Update min and max redeemable points
                    $this->minRedeemPoints = $settings->min_redeem_points ?? 0;
                    $this->updateLoyaltyValues();
                }
            }
            
            // Set loyalty redemption from order if exists
            if ($order && $order->loyalty_points_redeemed > 0) {
                $this->loyaltyPointsRedeemed = $order->loyalty_points_redeemed;
                $this->loyaltyDiscountAmount = $order->loyalty_discount_amount ?? 0;
            }
        } catch (\Exception $e) {
            Log::error('Failed to load loyalty data for order: ' . $e->getMessage());
        }
    }

    /**
     * Redeem loyalty points for an existing order (shop/order detail version)
     */
    public function redeemLoyaltyPointsForOrder($order, $points = null)
    {
        if (!$this->isLoyaltyEnabled() || !$order->customer_id || $order->status === 'paid') {
            return ['success' => false, 'message' => __('loyalty::app.cannotRedeemForPaidOrder')];
        }

        try {
            $loyaltyService = app(\Modules\Loyalty\Services\LoyaltyService::class);
            
            // Use provided points or available points
            $pointsToRedeem = $points ?? $this->availableLoyaltyPoints ?? 0;
            
            // Redeem points for the order
            $result = $loyaltyService->redeemPoints($order, $pointsToRedeem);
            
            if ($result['success']) {
                // Reload order to get updated data
                $order->refresh();
                
                // Update local properties
                $this->loyaltyPointsRedeemed = $result['points_redeemed'];
                $this->loyaltyDiscountAmount = $result['discount_amount'];
                
                // Reload loyalty data
                if (property_exists($this, 'restaurant') && property_exists($this, 'customer')) {
                    $this->loadLoyaltyDataForOrder($order, $this->restaurant->id, $this->customer->id, $order->sub_total);
                }
            }
            
            return $result;
        } catch (\Exception $e) {
            Log::error('Failed to redeem loyalty points for order: ' . $e->getMessage());
            return ['success' => false, 'message' => __('loyalty::app.failedToRedeemPoints')];
        }
    }

    /**
     * Remove loyalty redemption from an existing order (shop/order detail version)
     */
    public function removeLoyaltyRedemptionFromOrder($order)
    {
        if (!$this->isLoyaltyEnabled() || !$order->customer_id || $order->status === 'paid') {
            return false;
        }

        try {
            $loyaltyService = app(\Modules\Loyalty\Services\LoyaltyService::class);
            
            // Remove redemption
            $success = $loyaltyService->removeRedemption($order);
            
            if ($success) {
                // Reload order to get updated data
                $order->refresh();
                
                // Reset local properties
                $this->loyaltyPointsRedeemed = 0;
                $this->loyaltyDiscountAmount = 0;
                
                // Reload loyalty data
                if (property_exists($this, 'restaurant') && property_exists($this, 'customer')) {
                    $this->loadLoyaltyDataForOrder($order, $this->restaurant->id, $this->customer->id, $order->sub_total);
                }
            }
            
            return $success;
        } catch (\Exception $e) {
            Log::error('Failed to remove loyalty redemption from order: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Add loyalty points subquery to customer query builder
     * Static helper that can be used in CustomerTable or other places
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int|null $restaurantId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function addLoyaltyPointsToQuery($query, $restaurantId = null)
    {
        // Only add if module is enabled
        if (module_enabled('Loyalty')) {
            return $query;
        }
        
        // Check if LoyaltyAccount class exists
        if (!module_enabled('Loyalty')) {
            return $query;
        }
        
        try {
            // Use provided restaurant_id or get from restaurant() helper
            $restaurantId = $restaurantId ?? (function_exists('restaurant') ? restaurant()->id : null);
            
            if (!$restaurantId) {
                return $query;
            }
            
            // Add loyalty points as a subquery using addSelect - this works with withCount
            // The subquery selects points_balance from loyalty_accounts where customer_id and restaurant_id match
            // Use DB::table for more reliable subquery execution
            $loyaltyAccountTable = (new \Modules\Loyalty\Entities\LoyaltyAccount())->getTable();
            
            // Use a raw subquery with proper casting to ensure integer result
            $query->addSelect([
                'loyalty_points' => DB::table($loyaltyAccountTable)
                    ->selectRaw('CAST(COALESCE(points_balance, 0) AS UNSIGNED)')
                    ->whereColumn('customer_id', 'customers.id')
                    ->where('restaurant_id', $restaurantId)
                    ->limit(1)
            ]);
        } catch (\Exception $e) {
            // Silently fail if there's an error (module might not be properly configured)
            \Illuminate\Support\Facades\Log::debug('Error adding loyalty points to query: ' . $e->getMessage());
        }
        
        return $query;
    }

    /**
     * Load loyalty points for multiple customers (for customer table/list views)
     * Static helper that can be used without trait
     * Accepts both collections and paginators
     */
    public static function loadLoyaltyPointsForCustomers($customers, $restaurantId): void
    {
        if (!module_enabled('Loyalty')) {
            return;
        }

        if (!function_exists('restaurant_modules')) {
            return;
        }

        $restaurantModules = restaurant_modules();
        if (!in_array('Loyalty', $restaurantModules)) {
            return;
        }

        try {
            // Handle paginator objects - extract items if needed
            $isPaginator = is_object($customers) && method_exists($customers, 'items');
            
            if ($isPaginator) {
                // For paginators, get items array (items are objects, passed by reference)
                $customerItems = $customers->items();
            } else {
                // For collections/arrays, convert to array
                $customerItems = is_array($customers) ? $customers : $customers->all();
            }
            
            if (empty($customerItems)) {
                return;
            }
            
            // Get customer IDs from the items
            $customerIds = [];
            foreach ($customerItems as $customer) {
                if (is_object($customer) && isset($customer->id)) {
                    $customerIds[] = $customer->id;
                }
            }
            
            if (empty($customerIds)) {
                return;
            }
            
            // Fetch loyalty accounts
            $loyaltyAccounts = \Modules\Loyalty\Entities\LoyaltyAccount::where('restaurant_id', $restaurantId)
                ->whereIn('customer_id', $customerIds)
                ->pluck('points_balance', 'customer_id')
                ->toArray();
            
            // Debug: Log what we found
            \Illuminate\Support\Facades\Log::debug('Loading loyalty points for customers', [
                'restaurant_id' => $restaurantId,
                'customer_ids' => $customerIds,
                'accounts_found' => count($loyaltyAccounts),
                'accounts' => $loyaltyAccounts
            ]);
            
            // Set loyalty points on each customer object
            // IMPORTANT: Objects in PHP are passed by reference, so modifying $customerItems
            // will modify the actual objects in the paginator
            foreach ($customerItems as $customer) {
                if (is_object($customer) && isset($customer->id)) {
                    // Set the loyalty_points property directly on the model object
                    $points = $loyaltyAccounts[$customer->id] ?? 0;
                    $customer->loyalty_points = $points;
                    // Also set as attribute to ensure it's accessible
                    $customer->setAttribute('loyalty_points', $points);
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to load loyalty points for customers: ' . $e->getMessage(), [
                'restaurant_id' => $restaurantId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Recalculate loyalty discount amount based on redeemed points and current subtotal
     * This is used when subtotal changes after points are already redeemed
     */
    public function recalculateLoyaltyDiscount($restaurantId, $subTotal): void
    {
        if (!$this->isLoyaltyEnabled() || $this->loyaltyPointsRedeemed <= 0) {
            return;
        }

        try {
            $loyaltyService = app(\Modules\Loyalty\Services\LoyaltyService::class);
            $settings = \Modules\Loyalty\Entities\LoyaltySetting::getForRestaurant($restaurantId);
            
            if ($settings && $settings->isEnabled()) {
                // Calculate discount based on redeemed points and value per point
                $pointsDiscount = $this->loyaltyPointsRedeemed * $settings->value_per_point;
                
                // Only cap at max discount TODAY if subtotal > 0
                if ($subTotal > 0) {
                    // Calculate max discount TODAY (percentage of subtotal)
                    $maxDiscountToday = $subTotal * ($settings->max_discount_percent / 100);
                    
                    // Use the smaller of points discount or max discount TODAY
                    $this->loyaltyDiscountAmount = min($pointsDiscount, $maxDiscountToday);
                } else {
                    // If subtotal is 0, use the points discount directly
                    $this->loyaltyDiscountAmount = $pointsDiscount;
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to recalculate loyalty discount: ' . $e->getMessage());
        }
    }

    /**
     * Load tier information for customer
     * This method loads the current tier, next tier, and progress information
     */
    public function loadTierInformation()
    {
        // Check if tier functionality is available
        if (!class_exists(LoyaltyTier::class) || !class_exists(LoyaltyService::class)) {
            return;
        }

        // Check if required properties exist
        if (!property_exists($this, 'restaurant') || !property_exists($this, 'customer')) {
            return;
        }

        if (!$this->restaurant || !$this->customer) {
            return;
        }

        try {
            $restaurantId = $this->restaurant->id;
            $customerId = $this->customer->id;

            // Get loyalty account
            $loyaltyService = app(LoyaltyService::class);
            $account = $loyaltyService->getOrCreateAccount($restaurantId, $customerId);
            $pointsBalance = $account->points_balance;

            // Get current tier
            $this->currentTier = LoyaltyTier::getTierForPoints($restaurantId, $pointsBalance);

            if ($this->currentTier) {
                // Get next tier
                $this->nextTier = $this->currentTier->getNextTier();

                if ($this->nextTier) {
                    $this->pointsToNextTier = $this->currentTier->getPointsToNextTier($pointsBalance);

                    // Calculate progress percentage
                    $pointsInCurrentTier = $pointsBalance - $this->currentTier->min_points;
                    $pointsNeededForNextTier = $this->nextTier->min_points - $this->currentTier->min_points;
                    if ($pointsNeededForNextTier > 0) {
                        $this->tierProgress = min(100, ($pointsInCurrentTier / $pointsNeededForNextTier) * 100);
                    }
                } else {
                    // Already at highest tier
                    $this->tierProgress = 100;
                }
            }
        } catch (\Exception $e) {
            Log::error('Error loading tier information: ' . $e->getMessage());
        }
    }
}

