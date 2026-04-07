<?php

namespace Modules\Loyalty\Traits;

use App\Models\RestaurantCharge;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

trait HasKioskLoyaltyIntegration
{
    // Loyalty properties
    public $loyaltyPointsRedeemed = 0;
    public $loyaltyDiscountAmount = 0;
    public $availableLoyaltyPoints = 0;
    public $maxLoyaltyDiscount = 0;
    public $loyaltyPointsValue = 0;
    public $pointsToRedeem = 0;
    public $maxRedeemablePoints = 0;
    public $minRedeemPoints = 0;
    
    // Stamp redemption properties
    public $customerStamps = [];
    public $selectedStampRuleId = null; // Backward compatibility
    public $selectedStampRuleIds = [];
    public $stampDiscountAmount = 0;
    public $stampDiscountBreakdown = [];
    public $stampRedemptionCounts = [];

    protected function getApplicableKioskCharges(string $orderType): \Illuminate\Support\Collection
    {
        if (!class_exists(RestaurantCharge::class)) {
            return collect();
        }

        return RestaurantCharge::withoutGlobalScopes()
            ->where('restaurant_id', $this->restaurant->id ?? null)
            ->where('is_enabled', true)
            ->whereJsonContains('order_types', $orderType)
            ->get();
    }

    /**
     * Check if loyalty module is enabled for kiosk
     */
    public function isLoyaltyEnabled()
    {
        // Check if module is enabled
        if (!function_exists('module_enabled') || !module_enabled('Loyalty')) {
            return false;
        }
        
        // Check if module is in restaurant's package
        if (function_exists('restaurant_modules')) {
            $restaurantModules = restaurant_modules();
            if (!in_array('Loyalty', $restaurantModules)) {
                return false;
            }
        }
        
        // Check platform-specific setting for Kiosk (check both points and stamps)
        try {
            if (module_enabled('Loyalty')) {
                $restaurantId = $this->restaurant->id ?? null;
                if ($restaurantId) {
                    $settings = \Modules\Loyalty\Entities\LoyaltySetting::getForRestaurant($restaurantId);
                    if (!$settings->enabled) {
                        return false;
                    }
                    
                    // Check if points or stamps are enabled for kiosk
                    $pointsEnabled = $settings->enable_points && ($settings->enable_points_for_kiosk ?? true);
                    $stampsEnabled = $settings->enable_stamps && ($settings->enable_stamps_for_kiosk ?? true);
                    
                    // Return true if either points or stamps are enabled for kiosk
                    return $pointsEnabled || $stampsEnabled;
                }
            }
        } catch (\Exception $e) {
            // Silently fail
        }
        
        return false;
    }
    
    /**
     * Load loyalty points for customer (Kiosk-specific)
     */
    protected function loadLoyaltyPoints()
    {
        if (!$this->isLoyaltyEnabled() || !$this->customerId) {
            return;
        }
        
        try {
            if (!module_enabled('Loyalty')) {
                return;
            }
            
            $loyaltyService = app(\Modules\Loyalty\Services\LoyaltyService::class);
            $restaurantId = $this->restaurant->id;
            
            // Get available points
            $this->availableLoyaltyPoints = $loyaltyService->getAvailablePoints($restaurantId, $this->customerId);
            
            // Load loyalty settings
            if (module_enabled('Loyalty')) {
                $settings = \Modules\Loyalty\Entities\LoyaltySetting::getForRestaurant($restaurantId);
                $this->minRedeemPoints = $settings->min_redeem_points ?? 0;
                $this->loyaltyPointsValue = $settings->value_per_point ?? 1;
                
                // Get cart subtotal to calculate max discount amount (Kiosk-specific)
                if (class_exists('Modules\Kiosk\Services\KioskCartService')) {
                    $kioskServiceClass = 'Modules\Kiosk\Services\KioskCartService';
                    $kioskService = new $kioskServiceClass();
                    $cartItemList = $kioskService->getKioskCartSummary($this->shopBranch->id);
                    $subtotal = $cartItemList['sub_total'] ?? 0;
                } else {
                    $subtotal = 0;
                }
                
                // Calculate max discount TODAY (percentage of subtotal)
                $maxDiscountPercent = $settings->max_discount_percent ?? 0;
                $maxDiscountToday = 0;
                if ($subtotal > 0) {
                    $maxDiscountToday = $subtotal * ($maxDiscountPercent / 100);
                }
                $this->maxLoyaltyDiscount = $maxDiscountToday; // Store actual discount amount for display
                
                // Calculate max redeemable points based on max discount
                $maxPointsByDiscount = 0;
                if ($maxDiscountToday > 0 && $this->loyaltyPointsValue > 0) {
                    $maxPointsByDiscount = floor($maxDiscountToday / $this->loyaltyPointsValue);
                }
                
                $this->maxRedeemablePoints = min($this->availableLoyaltyPoints, $maxPointsByDiscount);
                
                // Ensure it's a multiple of min_redeem_points
                if ($this->minRedeemPoints > 0 && $this->maxRedeemablePoints > 0) {
                    $this->maxRedeemablePoints = floor($this->maxRedeemablePoints / $this->minRedeemPoints) * $this->minRedeemPoints;
                }
                
                // If no redemption yet, calculate what would be redeemed (preview only)
                if ($this->loyaltyPointsRedeemed == 0) {
                    $this->calculateMaxLoyaltyRedemption(false);
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to load loyalty points in Kiosk: ' . $e->getMessage());
        }
    }
    
    /**
     * Calculate maximum redeemable points and discount (Kiosk-specific)
     */
    public function calculateMaxLoyaltyRedemption($applyRedemption = false)
    {
        if (!$this->isLoyaltyEnabled() || !$this->customerId) {
            return;
        }
        
        if ($this->availableLoyaltyPoints <= 0) {
            $this->pointsToRedeem = 0;
            $this->loyaltyDiscountAmount = 0;
            if (!$applyRedemption) {
                $this->loyaltyPointsRedeemed = 0;
            }
            return;
        }
        
        try {
            if (!module_enabled('Loyalty')) {
                return;
            }
            
            $settings = \Modules\Loyalty\Entities\LoyaltySetting::getForRestaurant($this->restaurant->id);
            if (!$settings || !$settings->isEnabled()) {
                return;
            }
            
            // Get cart subtotal (Kiosk-specific)
            if (!class_exists('Modules\Kiosk\Services\KioskCartService')) {
                return;
            }
            $kioskServiceClass = 'Modules\Kiosk\Services\KioskCartService';
            $kioskService = new $kioskServiceClass();
            $cartItemList = $kioskService->getKioskCartSummary($this->shopBranch->id);
            $subtotal = $cartItemList['sub_total'] ?? 0;
            
            // Calculate max discount amount
            $maxDiscountPercent = $settings->max_discount_percent ?? 0;
            $maxDiscountAmount = ($subtotal * $maxDiscountPercent) / 100;
            
            // Calculate max points that can be redeemed (based on max discount)
            $valuePerPoint = $settings->value_per_point ?? 1;
            $maxPointsByDiscount = floor($maxDiscountAmount / $valuePerPoint);
            
            // Use the smaller of available points or max points by discount
            $pointsToRedeem = min($this->availableLoyaltyPoints, $maxPointsByDiscount);
            
            // Ensure it's a multiple of min_redeem_points
            if ($settings->min_redeem_points > 0 && $pointsToRedeem > 0) {
                $pointsToRedeem = floor($pointsToRedeem / $settings->min_redeem_points) * $settings->min_redeem_points;
            }
            
            // Ensure minimum redeem points
            if ($settings->min_redeem_points > 0 && $pointsToRedeem < $settings->min_redeem_points) {
                $pointsToRedeem = 0;
            }
            
            // Calculate base discount amount
            $basePointsDiscount = $pointsToRedeem * $valuePerPoint;
            
            // Apply tier redemption multiplier if customer has a tier
            $tierMultiplier = 1.00;
            if ($this->customerId && module_enabled('Loyalty')) {
                try {
                    if (module_enabled('Loyalty')) {
                        $loyaltyService = app(\Modules\Loyalty\Services\LoyaltyService::class);
                        $account = $loyaltyService->getOrCreateAccount($this->restaurant->id, $this->customerId);
                        if ($account && $account->tier_id) {
                            $tier = \Modules\Loyalty\Entities\LoyaltyTier::find($account->tier_id);
                            if ($tier && $tier->redemption_multiplier > 0) {
                                $tierMultiplier = $tier->redemption_multiplier;
                            }
                        }
                    }
                } catch (\Exception $e) {
                    // If tier check fails, use default multiplier of 1.00
                    Log::warning('Error checking tier for points redemption in Kiosk: ' . $e->getMessage());
                }
            }
            
            // Apply tier multiplier to discount
            $pointsDiscount = $basePointsDiscount * $tierMultiplier;
            $discountAmount = min($pointsDiscount, $maxDiscountAmount);
            
            // Set preview values (pointsToRedeem for display)
            $this->pointsToRedeem = $pointsToRedeem;
            
            // Only set discount amount and redeemed points if actually applying redemption
            if ($applyRedemption) {
                $this->loyaltyPointsRedeemed = $pointsToRedeem;
                $this->loyaltyDiscountAmount = $discountAmount;
            } else {
                // For preview, don't set discount amount - only show points to redeem
                $this->loyaltyDiscountAmount = 0;
            }
            
        } catch (\Exception $e) {
            Log::error('Failed to calculate max loyalty redemption in Kiosk: ' . $e->getMessage());
        }
    }
    
    /**
     * Redeem loyalty points (apply discount immediately) - Kiosk-specific
     */
    public function redeemLoyaltyPoints()
    {
        if (!$this->isLoyaltyEnabled() || !$this->customerId) {
            return;
        }
        
        // Ensure loyalty points are loaded
        if ($this->availableLoyaltyPoints <= 0) {
            $this->loadLoyaltyPoints();
        }
        
        // Calculate and apply maximum redemption
        $this->calculateMaxLoyaltyRedemption(true);
    }
    
    /**
     * Remove loyalty redemption
     */
    public function removeLoyaltyRedemption()
    {
        $this->loyaltyPointsRedeemed = 0;
        $this->loyaltyDiscountAmount = 0;
        $this->pointsToRedeem = 0;
    }
    
    /**
     * Calculate totals with loyalty discount applied (Kiosk-specific)
     * This method should be called from render() method
     */
    protected function calculateTotalsWithLoyaltyDiscount($cartItemList)
    {
        // Stamp discount is already applied to item prices, so cart subtotal is already discounted
        // We need to calculate the original subtotal (before stamp discount) for the order
        $discountedSubtotal = $cartItemList['sub_total']; // Current subtotal (after stamp discount on items)
        $originalSubtotal = $discountedSubtotal;
        
        // Add back stamp discount to get original subtotal (before any discounts)
        if ($this->stampDiscountAmount > 0) {
            $originalSubtotal = $discountedSubtotal + $this->stampDiscountAmount;
        }
        
        $taxMode = $cartItemList['tax_mode'];
        $preLoyaltySubtotal = $discountedSubtotal;
        
        // Apply loyalty discount to discounted subtotal (after stamp discount)
        if ($this->isLoyaltyEnabled() && $this->loyaltyPointsRedeemed > 0 && $this->loyaltyDiscountAmount > 0) {
            $discountedSubtotal = max(0, $discountedSubtotal - $this->loyaltyDiscountAmount);
        }
        
        // Step 2: Apply service charges on net (after stamp + loyalty discounts)
        $serviceTotal = 0;
        $chargesBreakdown = [];
        $orderType = $cartItemList['order_type'] ?? 'dine_in';
        $charges = $this->getApplicableKioskCharges($orderType);
        foreach ($charges as $charge) {
            if (is_object($charge) && method_exists($charge, 'getAmount')) {
                $chargeAmount = $charge->getAmount($discountedSubtotal);
                $serviceTotal += $chargeAmount;
                $chargesBreakdown[] = [
                    'name' => $charge->charge_name ?? __('charges.charge'),
                    'amount' => round($chargeAmount, 2),
                ];
            }
        }

        // Step 3: Recalculate taxes on discounted subtotal (+ service charges if configured)
        $totalTaxAmount = 0;
        $taxBreakdown = [];
        
        $taxBase = null;
        if ($taxMode === 'order') {
            // Order-level taxation - calculate on discounted subtotal
            if (class_exists(\App\Models\Tax::class)) {
                $taxes = \App\Models\Tax::withoutGlobalScopes()
                    ->where('restaurant_id', $this->restaurant->id)
                    ->get();
                
                $includeChargesInTaxBase = $this->restaurant->include_charges_in_tax_base ?? false;
                $taxBase = $includeChargesInTaxBase ? ($discountedSubtotal + $serviceTotal) : $discountedSubtotal;

                foreach ($taxes as $tax) {
                    $taxAmount = ($tax->tax_percent / 100) * $taxBase;
                    $totalTaxAmount += $taxAmount;
                    $taxBreakdown[$tax->tax_name] = [
                        'percent' => $tax->tax_percent,
                        'amount' => round($taxAmount, 2)
                    ];
                }
            }
        } else {
            // Item-level taxation - use original tax breakdown (taxes are on items, not order)
            $originalTaxAmount = $cartItemList['total_tax_amount'] ?? 0;
            $ratio = ($preLoyaltySubtotal > 0) ? ($discountedSubtotal / $preLoyaltySubtotal) : 0;
            $totalTaxAmount = round($originalTaxAmount * $ratio, 2);
            $taxBreakdown = $cartItemList['tax_breakdown'] ?? [];
            if (!empty($taxBreakdown)) {
                foreach ($taxBreakdown as $name => $data) {
                    $taxBreakdown[$name]['amount'] = round(($data['amount'] ?? 0) * $ratio, 2);
                }
            }
        }
        
        // Calculate total: discounted subtotal + service charges + tax
        $total = $discountedSubtotal + $serviceTotal;
        if ($taxMode === 'order') {
            // Order-level: always add tax (exclusive)
            $total += $totalTaxAmount;
        } else {
            // Item-level: add tax only if exclusive
            $isInclusive = $this->restaurant->tax_inclusive ?? false;
            if (!$isInclusive) {
                $total += $totalTaxAmount;
            }
        }
        
        return [
            'subtotal' => $originalSubtotal, // Original subtotal before any discounts (for order.sub_total)
            'discountedSubtotal' => $discountedSubtotal, // Subtotal after both stamp and loyalty discounts
            'total' => $total,
            'totalTaxAmount' => $totalTaxAmount,
            'taxBreakdown' => $taxBreakdown,
            'serviceTotal' => $serviceTotal,
            'chargeBreakdown' => $chargesBreakdown,
            'taxBase' => $taxBase,
        ];
    }
    
    /**
     * Process loyalty redemption during order creation (Kiosk-specific)
     */
    protected function processLoyaltyRedemptionForOrder($order, $customer)
    {
        if (!$this->isLoyaltyEnabled() || !$this->loyaltyPointsRedeemed || !$this->loyaltyDiscountAmount || !$customer) {
            return;
        }
        
        try {
            if (module_enabled('Loyalty')) {
                $loyaltyService = app(\Modules\Loyalty\Services\LoyaltyService::class);
                $result = $loyaltyService->redeemPoints($order, $this->loyaltyPointsRedeemed);
                
                if (!$result['success']) {
                    // Redemption failed - clear discount and recalculate totals
                    $order->update([
                        'loyalty_points_redeemed' => 0,
                        'loyalty_discount_amount' => 0,
                    ]);
                    
                    // Recalculate totals without loyalty discount
                    $this->recalculateOrderTotalsWithoutLoyalty($order);
                    $order->refresh();
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to redeem loyalty points in Kiosk: ' . $e->getMessage());
            
            // Recalculate totals without loyalty discount on error
            $order->update([
                'loyalty_points_redeemed' => 0,
                'loyalty_discount_amount' => 0,
            ]);
            $this->recalculateOrderTotalsWithoutLoyalty($order);
            $order->refresh();
        }
    }
    
    /**
     * Recalculate order totals without loyalty discount
     */
    protected function recalculateOrderTotalsWithoutLoyalty($order)
    {
        if (!class_exists('Modules\Kiosk\Services\KioskCartService')) {
            return;
        }
        $kioskServiceClass = 'Modules\Kiosk\Services\KioskCartService';
        $kioskService = new $kioskServiceClass();
        $cartItemList = $kioskService->getKioskCartSummary($this->shopBranch->id);
        
        $orderSubTotal = $cartItemList['sub_total'];
        $taxMode = $cartItemList['tax_mode'];
        
        // Apply service charges on net (no loyalty discount, stamp already in items)
        $serviceTotal = 0;
        $charges = $this->getApplicableKioskCharges($cartItemList['order_type'] ?? 'dine_in');
        foreach ($charges as $charge) {
            if (is_object($charge) && method_exists($charge, 'getAmount')) {
                $serviceTotal += $charge->getAmount($orderSubTotal);
            }
        }

        // Recalculate taxes on original subtotal (and charges if configured)
        $orderTotalTaxAmount = 0;
        if ($taxMode === 'order') {
            if (class_exists(\App\Models\Tax::class)) {
                $taxes = \App\Models\Tax::withoutGlobalScopes()->where('restaurant_id', $this->restaurant->id)->get();
                $includeChargesInTaxBase = $this->restaurant->include_charges_in_tax_base ?? false;
                $taxBase = $includeChargesInTaxBase ? ($orderSubTotal + $serviceTotal) : $orderSubTotal;
                foreach ($taxes as $tax) {
                    $taxAmount = ($tax->tax_percent / 100) * $taxBase;
                    $orderTotalTaxAmount += $taxAmount;
                }
            }
        } else {
            $orderTotalTaxAmount = $cartItemList['total_tax_amount'] ?? 0;
        }
        
        // Calculate total: original subtotal + tax
        $orderTotal = $orderSubTotal + $serviceTotal;
        if ($taxMode === 'order') {
            $orderTotal += $orderTotalTaxAmount;
        } else {
            $isInclusive = $this->restaurant->tax_inclusive ?? false;
            if (!$isInclusive) {
                $orderTotal += $orderTotalTaxAmount;
            }
        }
        
        $includeChargesInTaxBase = $this->restaurant->include_charges_in_tax_base ?? false;
        $taxBase = $taxMode === 'order'
            ? ($includeChargesInTaxBase ? ($orderSubTotal + $serviceTotal) : $orderSubTotal)
            : null;

        $order->update([
            'total' => $orderTotal,
            'total_tax_amount' => $orderTotalTaxAmount,
            'tax_base' => $taxBase,
        ]);
    }
    
    /**
     * Check if points are enabled for kiosk
     */
    public function isPointsEnabledForKiosk()
    {
        if (!$this->isLoyaltyEnabled()) {
            return false;
        }
        
        try {
            if (module_enabled('Loyalty')) {
                $restaurantId = $this->restaurant->id ?? null;
                if ($restaurantId) {
                    $settings = \Modules\Loyalty\Entities\LoyaltySetting::getForRestaurant($restaurantId);
                    $loyaltyType = $settings->loyalty_type ?? 'points';
                    $pointsEnabled = in_array($loyaltyType, ['points', 'both']) && ($settings->enable_points ?? true);
                    return $pointsEnabled && ($settings->enable_points_for_kiosk ?? true);
                }
            }
        } catch (\Exception $e) {
            // Silently fail
        }
        
        return false;
    }
    
    /**
     * Check if stamps are enabled for kiosk
     */
    public function isStampsEnabledForKiosk()
    {
        if (!$this->isLoyaltyEnabled()) {
            return false;
        }
        
        try {
            if (module_enabled('Loyalty')) {
                $restaurantId = $this->restaurant->id ?? null;
                if ($restaurantId) {
                    $settings = \Modules\Loyalty\Entities\LoyaltySetting::getForRestaurant($restaurantId);
                    $loyaltyType = $settings->loyalty_type ?? 'points';
                    $stampsEnabled = in_array($loyaltyType, ['stamps', 'both']) && ($settings->enable_stamps ?? true);
                    return $stampsEnabled && ($settings->enable_stamps_for_kiosk ?? true);
                }
            }
        } catch (\Exception $e) {
            // Silently fail
        }
        
        return false;
    }
    
    /**
     * Load customer stamps for stamp redemption
     */
    protected function loadCustomerStamps()
    {
        if (!$this->isStampsEnabledForKiosk() || !$this->customerId) {
            $this->customerStamps = [];
            return;
        }
        
        try {
            if (!module_enabled('Loyalty')) {
                return;
            }
            
            $loyaltyService = app(\Modules\Loyalty\Services\LoyaltyService::class);
            $restaurantId = $this->restaurant->id;
            
            // Get customer stamps with rules
            $allStamps = $loyaltyService->getCustomerStamps($restaurantId, $this->customerId);
            
            // Filter stamps to only show those where cart contains items matching the stamp rule
            // Get cart items from Kiosk cart service
            $cartService = new \Modules\Kiosk\Services\KioskCartService();
            $branchId = $this->shopBranch->id ?? null;
            
            if (!$branchId) {
                $this->customerStamps = [];
                return;
            }
            
            $cartSummary = $cartService->getKioskCartSummary($branchId);
            $cartMenuItemIds = [];
            
            // Handle both array and collection for items
            $cartItems = $cartSummary['items'] ?? [];
            if ($cartItems instanceof \Illuminate\Support\Collection) {
                $cartItems = $cartItems->toArray();
            }
            
            if (!empty($cartItems)) {
                foreach ($cartItems as $cartItem) {
                    // Get menu_item_id from the nested structure
                    if (isset($cartItem['menu_item']['id'])) {
                        $cartMenuItemIds[] = $cartItem['menu_item']['id'];
                    }
                }
            }
            
            // Debug logging
            \Illuminate\Support\Facades\Log::info('Kiosk Stamp Filter Debug', [
                'cart_menu_item_ids' => $cartMenuItemIds,
                'all_stamps_count' => count($allStamps),
            ]);
            
            // Filter stamps: only show if the stamp rule's menu_item_id matches an item in cart
            $this->customerStamps = collect($allStamps)->filter(function ($stampData) use ($cartMenuItemIds) {
                $rule = $stampData['rule'] ?? null;
                if (!$rule) {
                    return false;
                }
                
                // Check if this stamp rule's menu item is in the cart
                $ruleMenuItemId = $rule->menu_item_id ?? null;
                $shouldShow = $ruleMenuItemId && in_array($ruleMenuItemId, $cartMenuItemIds);
                
                // Debug logging
                \Illuminate\Support\Facades\Log::info('Stamp Filter Check', [
                    'rule_id' => $rule->id ?? 'unknown',
                    'rule_menu_item_id' => $ruleMenuItemId,
                    'should_show' => $shouldShow,
                ]);
                
                return $shouldShow;
            })->values()->toArray();
            
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to load customer stamps in Kiosk: ' . $e->getMessage());
            $this->customerStamps = [];
        }
    }
    
    /**
     * Redeem stamps for kiosk order
     * Apply discount to cart item prices, then recalculate subtotal
     */
    public function redeemStamps($stampRuleId = null)
    {
        if (!$this->isStampsEnabledForKiosk() || !$this->customerId) {
            return;
        }
        
        $stampRuleId = $stampRuleId ?? $this->selectedStampRuleId;
        
        if (!$stampRuleId) {
            return;
        }
        
        // Set selected stamp rule(s)
        $this->selectedStampRuleId = $stampRuleId;
        if (!is_array($this->selectedStampRuleIds)) {
            $this->selectedStampRuleIds = [];
        }
        if (in_array($stampRuleId, $this->selectedStampRuleIds, true)) {
            return;
        }
        $this->selectedStampRuleIds[] = $stampRuleId;
        
        try {
            if (module_enabled('Loyalty')) {
                $stampRule = \Modules\Loyalty\Entities\LoyaltyStampRule::find($stampRuleId);
                
                if ($stampRule) {
                    $kioskServiceClass = 'Modules\Kiosk\Services\KioskCartService';
                    if (class_exists($kioskServiceClass)) {
                        // Handle free item reward
                        if ($stampRule->reward_type === 'free_item') {
                            // For free items, discount is calculated during order creation
                            // Just mark it as selected
                            $this->stampDiscountAmount = 0;
                            return;
                        }
                        // Apply discount rules (supports multiple selections)
                        $this->applyStampRedemptionsToCart();
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to redeem stamps in Kiosk: ' . $e->getMessage());
            $this->stampDiscountAmount = 0;
        }
    }
    
    /**
     * Remove stamp redemption - restore original item prices
     */
    public function removeStampRedemption($stampRuleId = null)
    {
        if (!$this->selectedStampRuleId && empty($this->selectedStampRuleIds)) {
            return;
        }
        $stampRuleId = $stampRuleId ?? $this->selectedStampRuleId;
        if ($stampRuleId && is_array($this->selectedStampRuleIds)) {
            $this->selectedStampRuleIds = array_values(array_filter(
                $this->selectedStampRuleIds,
                fn($id) => (int)$id !== (int)$stampRuleId
            ));
        } else {
            $this->selectedStampRuleIds = [];
        }

        // Clear single selection if it no longer exists
        if ($this->selectedStampRuleId && !in_array($this->selectedStampRuleId, $this->selectedStampRuleIds, true)) {
            $this->selectedStampRuleId = null;
        }

        try {
            $this->applyStampRedemptionsToCart();
        } catch (\Exception $e) {
            Log::error('Failed to remove stamp redemption in Kiosk: ' . $e->getMessage());
        }
    }

    /**
     * Apply all selected stamp redemptions to cart (multi-select support)
     */
    protected function applyStampRedemptionsToCart(): void
    {
        $kioskServiceClass = 'Modules\Kiosk\Services\KioskCartService';
        if (!class_exists($kioskServiceClass)) {
            return;
        }
        $kioskService = new $kioskServiceClass();
        $cartSession = $kioskService->getCurrentCartSession($this->shopBranch->id);
        if (!$cartSession) {
            return;
        }

        $cartSession->load(['cartItems.menuItem.taxes', 'cartItems.menuItemVariation', 'cartItems.modifiers', 'branch.restaurant']);

        // Reset cart items to base amounts (no stamp discounts applied)
        foreach ($cartSession->cartItems as $cartItem) {
            $basePrice = $cartItem->menuItemVariation
                ? $cartItem->menuItemVariation->price
                : ($cartItem->menuItem->price ?? 0);
            $modifierPrice = $cartItem->modifiers->sum('price') ?? 0;
            $itemPrice = ($basePrice + $modifierPrice) * $cartItem->quantity;
            $cartItem->amount = $itemPrice;

            if ($cartSession->tax_mode === 'item') {
                $taxes = $cartItem->menuItem->taxes ?? collect();
                $isInclusive = $cartSession->branch->restaurant->tax_inclusive ?? false;
                if ($taxes->isNotEmpty()) {
                    $taxResult = \App\Models\MenuItem::calculateItemTaxes($basePrice + $modifierPrice, $taxes, $isInclusive);
                    $cartItem->tax_amount = ($taxResult['tax_amount'] ?? 0) * $cartItem->quantity;
                    $cartItem->tax_percentage = $taxResult['tax_percentage'] ?? 0;
                    $cartItem->tax_breakup = !empty($taxResult['tax_breakdown']) ? json_encode($taxResult['tax_breakdown']) : null;
                } else {
                    $cartItem->tax_amount = 0;
                    $cartItem->tax_percentage = 0;
                    $cartItem->tax_breakup = null;
                }
            }
            $cartItem->save();
        }

        $this->stampDiscountBreakdown = [];
        $this->stampDiscountAmount = 0;
        $this->stampRedemptionCounts = [];

        $selectedRuleIds = is_array($this->selectedStampRuleIds) ? $this->selectedStampRuleIds : [];
        foreach ($selectedRuleIds as $ruleId) {
            $stampRule = \Modules\Loyalty\Entities\LoyaltyStampRule::find($ruleId);
            if (!$stampRule || $stampRule->reward_type === 'free_item') {
                continue;
            }

            $cartItems = $cartSession->cartItems->filter(function($item) use ($stampRule) {
                return $item->menu_item_id == $stampRule->menu_item_id;
            });

            if ($cartItems->isEmpty()) {
                continue;
            }

            $stampData = collect($this->customerStamps ?? [])->first(function ($data) use ($ruleId) {
                return isset($data['rule']) && ($data['rule']->id ?? null) == $ruleId;
            });
            $availableStamps = (int)($stampData['available_stamps'] ?? 0);
            $stampsRequired = (int)($stampData['stamps_required'] ?? 0);
            $maxItemsToRedeem = ($stampsRequired > 0) ? intdiv($availableStamps, $stampsRequired) : 0;
            if ($maxItemsToRedeem <= 0) {
                continue;
            }

            $tierMultiplier = 1.00;
            if (module_enabled('Loyalty')) {
                $account = \Modules\Loyalty\Entities\LoyaltyAccount::where('restaurant_id', $this->restaurant->id)
                    ->where('customer_id', $this->customerId)
                    ->first();
                if ($account && $account->tier_id && module_enabled('Loyalty')) {
                    $tier = \Modules\Loyalty\Entities\LoyaltyTier::find($account->tier_id);
                    if ($tier && $tier->redemption_multiplier > 0) {
                        $tierMultiplier = $tier->redemption_multiplier;
                    }
                }
            }

            $totalDiscountApplied = 0;
            $itemsRedeemed = 0;
            foreach ($cartItems as $cartItem) {
                $originalAmount = $cartItem->amount ?? 0;
                if ($originalAmount <= 0) {
                    continue;
                }

                if ($itemsRedeemed >= $maxItemsToRedeem) {
                    break;
                }

                $quantity = (int)($cartItem->quantity ?? 1);
                if ($quantity <= 0) {
                    continue;
                }

                $unitsToRedeem = min($quantity, $maxItemsToRedeem - $itemsRedeemed);
                if ($unitsToRedeem <= 0) {
                    continue;
                }

                $unitAmount = $originalAmount / $quantity;
                $itemDiscount = 0;
                if ($stampRule->reward_type === 'discount_percent') {
                    $baseItemDiscount = ($unitAmount * $stampRule->reward_value) / 100;
                    $itemDiscount = $baseItemDiscount * $tierMultiplier;
                } elseif ($stampRule->reward_type === 'discount_amount') {
                    $baseItemDiscount = min($stampRule->reward_value, $unitAmount);
                    $itemDiscount = $baseItemDiscount * $tierMultiplier;
                }

                $itemDiscount = round($itemDiscount * $unitsToRedeem, 2);
                $cartItem->amount = round(max(0, $originalAmount - $itemDiscount), 2);
                $cartItem->save();
                $totalDiscountApplied += $itemDiscount;
                $itemsRedeemed += $unitsToRedeem;
            }

            if ($totalDiscountApplied > 0) {
                $this->stampDiscountBreakdown[$ruleId] = $totalDiscountApplied;
                $this->stampDiscountAmount += $totalDiscountApplied;
                $this->stampRedemptionCounts[$ruleId] = $itemsRedeemed;
            }
        }

        // Recalculate cart totals using CartSessionService
        $cartSessionService = app(\App\Services\CartSessionService::class);
        $reflection = new \ReflectionClass($cartSessionService);
        $method = $reflection->getMethod('updateCartTotals');
        $method->setAccessible(true);
        $method->invoke($cartSessionService, $cartSession);
    }
    
    /**
     * Process stamp redemption during order creation (Kiosk-specific)
     */
    protected function processStampRedemptionForOrder($order, $customer)
    {
        $stampRuleIds = is_array($this->selectedStampRuleIds) ? $this->selectedStampRuleIds : [];
        if (empty($stampRuleIds) && $this->selectedStampRuleId) {
            $stampRuleIds = [$this->selectedStampRuleId];
        }

        if (!$this->isStampsEnabledForKiosk() || empty($stampRuleIds) || !$customer) {
            return;
        }
        
        try {
            // Preserve loyalty values before stamp redemption
            $preservedLoyaltyPointsRedeemed = $order->loyalty_points_redeemed ?? 0;
            $preservedLoyaltyDiscountAmount = $order->loyalty_discount_amount ?? 0;
            $preservedStampDiscountAmount = $order->stamp_discount_amount ?? $this->stampDiscountAmount;
            
            if (module_enabled('Loyalty')) {
                $loyaltyService = app(\Modules\Loyalty\Services\LoyaltyService::class);
                foreach ($stampRuleIds as $stampRuleId) {
                    $stampRule = \Modules\Loyalty\Entities\LoyaltyStampRule::find($stampRuleId);
                    if (!$stampRule) {
                        continue;
                    }

                    if (in_array($stampRule->reward_type, ['discount_percent', 'discount_amount'])) {
                        // Discount already applied to cart amounts; only record redemption + link items
                        $this->finalizeDiscountStampRedemption($order, $stampRuleId);
                        continue;
                    }

                    // Free item reward - use service to add item
                    $result = $loyaltyService->redeemStamps($order, $stampRuleId);
                    
                    if ($result['success']) {
                        // Reload order to get updated items/discounts
                        $order->refresh();
                        $order->load('items');
                        
                        // Ensure loyalty values are preserved (redeemStamps should preserve them, but double-check)
                        if ($preservedLoyaltyPointsRedeemed > 0 || $preservedLoyaltyDiscountAmount > 0) {
                            $order->loyalty_points_redeemed = $preservedLoyaltyPointsRedeemed;
                            $order->loyalty_discount_amount = $preservedLoyaltyDiscountAmount;
                            $order->save();
                        }

                        // Preserve stamp discount if discounts were already applied to cart
                        if ($preservedStampDiscountAmount > 0) {
                            $order->stamp_discount_amount = $preservedStampDiscountAmount;
                            $order->save();
                        }
                        
                        // Update stamp discount amount
                        $this->stampDiscountAmount = $order->stamp_discount_amount ?? 0;
                        
                        // Recalculate totals after stamp redemption (preserving loyalty values)
                        $this->recalculateOrderTotalAfterStampRedemption($order);
                    } else {
                        Log::error('STAMP REDEEM FAILED in Kiosk', [
                            'order_id' => $order->id,
                            'stamp_rule_id' => $stampRuleId,
                            'error' => $result['message'] ?? 'Unknown',
                        ]);
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to redeem stamps in Kiosk: ' . $e->getMessage());
        }
    }

    protected function finalizeDiscountStampRedemption($order, int $stampRuleId): void
    {
        try {
            if (!module_enabled('Loyalty')) {
                return;
            }

            $stampRule = \Modules\Loyalty\Entities\LoyaltyStampRule::find($stampRuleId);
            if (!$stampRule) {
                return;
            }

            $itemsRedeemed = (int)($this->stampRedemptionCounts[$stampRuleId] ?? 0);
            if ($itemsRedeemed <= 0) {
                return;
            }

            // Avoid double redemption (use total stamps to derive already-redeemed items)
            $existingStampsRedeemed = (int)\Modules\Loyalty\Entities\LoyaltyStampTransaction::where('order_id', $order->id)
                ->where('stamp_rule_id', $stampRuleId)
                ->where('type', 'REDEEM')
                ->sum(DB::raw('ABS(stamps)'));
            $stampsRequired = (int)$stampRule->stamps_required;
            $alreadyRedeemedItems = ($stampsRequired > 0) ? intdiv($existingStampsRedeemed, $stampsRequired) : 0;
            $itemsToRedeem = $itemsRedeemed - $alreadyRedeemedItems;
            if ($itemsToRedeem <= 0) {
                return;
            }

            // Update order items with stamp_rule_id
            $order->items()
                ->where('menu_item_id', $stampRule->menu_item_id)
                ->where(function ($q) {
                    $q->whereNull('is_free_item_from_stamp')
                      ->orWhere('is_free_item_from_stamp', false);
                })
                ->update(['stamp_rule_id' => $stampRuleId]);

            $customerStamp = \Modules\Loyalty\Entities\CustomerStamp::getOrCreate(
                $this->restaurant->id,
                $order->customer_id,
                $stampRuleId
            );

            $stampsToRedeem = $itemsToRedeem * $stampsRequired;
            $customerStamp->stamps_redeemed += $stampsToRedeem;
            $customerStamp->last_redeemed_at = now();
            $customerStamp->save();

            for ($i = 0; $i < $itemsToRedeem; $i++) {
                \Modules\Loyalty\Entities\LoyaltyStampTransaction::create([
                    'restaurant_id' => $this->restaurant->id,
                    'customer_id' => $order->customer_id,
                    'stamp_rule_id' => $stampRuleId,
                    'order_id' => $order->id,
                    'type' => 'REDEEM',
                    'stamps' => -$stampsRequired,
                    'reason' => __('loyalty::app.stampsRedeemedForOrder', [
                        'order_number' => $order->order_number,
                    ]),
                ]);
            }

            // Persist stamp discount amount on order
            if ($this->stampDiscountAmount > 0) {
                $order->stamp_discount_amount = $this->stampDiscountAmount;
                $order->save();
            }
        } catch (\Exception $e) {
            Log::error('Failed to finalize discount stamp redemption in Kiosk: ' . $e->getMessage());
        }
    }
    
    /**
     * Recalculate order total after stamp redemption
     */
    protected function recalculateOrderTotalAfterStampRedemption($order)
    {
        try {
            // Reload order with all relationships to get accurate item amounts
            $order->refresh();
            $order->load(['items', 'taxes.tax', 'charges.charge']);
            
            // Calculate subtotal from order items (includes stamp discount on items, and free items have amount=0)
            $correctSubTotal = (float)($order->items->sum('amount') ?? 0);
            $discountedSubTotal = (float)$correctSubTotal;
            
            // Apply regular discount if any
            $discountedSubTotal -= (float)($order->discount_amount ?? 0);
            
            // Apply loyalty discount BEFORE tax calculation
            $discountedSubTotal -= (float)($order->loyalty_discount_amount ?? 0);
            
            // Apply service charges on net after discounts
            $serviceTotal = 0.0;
            if ($order->charges && $order->charges->count() > 0) {
                foreach ($order->charges as $chargeRelation) {
                    $charge = $chargeRelation->charge;
                    if ($charge) {
                        $serviceTotal += $charge->getAmount(max(0, (float)$discountedSubTotal));
                    }
                }
            }

            // Calculate taxes on discounted subtotal (AFTER regular and loyalty discounts, stamp discount is already in items)
            $includeChargesInTaxBase = $this->restaurant->include_charges_in_tax_base ?? false;
            $taxBase = $includeChargesInTaxBase ? (max(0, (float)$discountedSubTotal) + $serviceTotal) : max(0, (float)$discountedSubTotal);
            $correctTaxAmount = 0.0;
            $taxMode = $order->tax_mode ?? 'order';
            
            if ($taxMode === 'order') {
                // Order-level taxes - calculate on discounted subtotal
                if (class_exists(\App\Models\Tax::class)) {
                    $taxes = \App\Models\Tax::withoutGlobalScopes()
                        ->where('restaurant_id', $this->restaurant->id)
                        ->get();
                    
                    foreach ($taxes as $tax) {
                        if (isset($tax->tax_percent)) {
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
            
            // Start total calculation from discounted subtotal
            $correctTotal = max(0, (float)$discountedSubTotal);
            $correctTotal += (float)$serviceTotal;
            
            // Add taxes to total
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
                'tax_base' => $taxBase,
            ];
            
            // Preserve loyalty fields if they exist (in case points were also redeemed)
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
            Log::error('Failed to recalculate order total after stamp redemption in Kiosk: ' . $e->getMessage());
        }
    }
    
    /**
     * Load customer and loyalty data (points and stamps)
     * This method should be called from mount() or when customer is set
     */
    protected function loadCustomerAndLoyaltyData()
    {
        // Load loyalty points if enabled
        if ($this->isPointsEnabledForKiosk() && $this->customerId) {
            $this->loadLoyaltyPoints();
        }
        
        // Load customer stamps if enabled
        if ($this->isStampsEnabledForKiosk() && $this->customerId) {
            $this->loadCustomerStamps();
        }
    }
}
