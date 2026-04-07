<?php

namespace Modules\Loyalty\Services;

use App\Models\Tax;
use App\Models\MenuItem;
use App\Services\CartSessionService;
use Illuminate\Support\Facades\DB;

class KioskLoyaltyHandler
{
    protected $component;

    public function __construct($component)
    {
        $this->component = $component;
    }

    protected function c()
    {
        return $this->component;
    }

    protected function isModuleEnabled(): bool
    {
        return function_exists('module_enabled') && module_enabled('Loyalty');
    }

    public function isLoyaltyEnabled(): bool
    {
        if (!$this->isModuleEnabled()) {
            return false;
        }

        if (function_exists('restaurant_modules')) {
            $restaurantModules = restaurant_modules();
            if (!in_array('Loyalty', $restaurantModules)) {
                return false;
            }
        }

        try {
            $restaurantId = $this->c()->restaurant->id ?? null;
            if ($restaurantId) {
                $settings = \Modules\Loyalty\Entities\LoyaltySetting::getForRestaurant($restaurantId);
                if (!$settings->enabled) {
                    return false;
                }

                $pointsEnabled = $settings->enable_points && ($settings->enable_points_for_kiosk ?? true);
                $stampsEnabled = $settings->enable_stamps && ($settings->enable_stamps_for_kiosk ?? true);

                return $pointsEnabled || $stampsEnabled;
            }
        } catch (\Exception $e) {
            return false;
        }

        return false;
    }

    public function isPointsEnabledForKiosk(): bool
    {
        if (!$this->isModuleEnabled()) {
            return false;
        }

        try {
            $restaurantId = $this->c()->restaurant->id ?? null;
            if ($restaurantId) {
                $settings = \Modules\Loyalty\Entities\LoyaltySetting::getForRestaurant($restaurantId);
                $loyaltyType = $settings->loyalty_type ?? 'points';
                $pointsEnabled = in_array($loyaltyType, ['points', 'both']) && ($settings->enable_points ?? true);
                return $pointsEnabled && ($settings->enable_points_for_kiosk ?? true);
            }
        } catch (\Exception $e) {
            return false;
        }

        return false;
    }

    public function isStampsEnabledForKiosk(): bool
    {
        if (!$this->isModuleEnabled()) {
            return false;
        }

        try {
            $restaurantId = $this->c()->restaurant->id ?? null;
            if ($restaurantId) {
                $settings = \Modules\Loyalty\Entities\LoyaltySetting::getForRestaurant($restaurantId);
                $loyaltyType = $settings->loyalty_type ?? 'points';
                $stampsEnabled = in_array($loyaltyType, ['stamps', 'both']) && ($settings->enable_stamps ?? true);
                return $stampsEnabled && ($settings->enable_stamps_for_kiosk ?? true);
            }
        } catch (\Exception $e) {
            return false;
        }

        return false;
    }

    public function loadLoyaltyPoints(): void
    {
        if (!$this->isLoyaltyEnabled() || !$this->c()->customerId) {
            return;
        }

        try {
            $loyaltyService = app(\Modules\Loyalty\Services\LoyaltyService::class);
            $restaurantId = $this->c()->restaurant->id;

            $this->c()->availableLoyaltyPoints = $loyaltyService->getAvailablePoints($restaurantId, $this->c()->customerId);

            $settings = \Modules\Loyalty\Entities\LoyaltySetting::getForRestaurant($restaurantId);
            $this->c()->minRedeemPoints = $settings->min_redeem_points ?? 0;
            $this->c()->loyaltyPointsValue = $settings->value_per_point ?? 1;

            $subtotal = 0;
            if (class_exists('Modules\Kiosk\Services\KioskCartService')) {
                $kioskService = new \Modules\Kiosk\Services\KioskCartService();
                $cartItemList = $kioskService->getKioskCartSummary($this->c()->shopBranch->id);
                $subtotal = $cartItemList['sub_total'] ?? 0;
            }

            $maxDiscountPercent = $settings->max_discount_percent ?? 0;
            $maxDiscountToday = $subtotal > 0 ? ($subtotal * ($maxDiscountPercent / 100)) : 0;
            $this->c()->maxLoyaltyDiscount = $maxDiscountToday;

            $maxPointsByDiscount = 0;
            if ($maxDiscountToday > 0 && $this->c()->loyaltyPointsValue > 0) {
                $maxPointsByDiscount = floor($maxDiscountToday / $this->c()->loyaltyPointsValue);
            }

            $this->c()->maxRedeemablePoints = min($this->c()->availableLoyaltyPoints, $maxPointsByDiscount);

            if ($this->c()->minRedeemPoints > 0 && $this->c()->maxRedeemablePoints > 0) {
                $this->c()->maxRedeemablePoints = floor($this->c()->maxRedeemablePoints / $this->c()->minRedeemPoints) * $this->c()->minRedeemPoints;
            }

            if ($this->c()->loyaltyPointsRedeemed == 0) {
                $this->calculateMaxLoyaltyRedemption(false);
            }
        } catch (\Exception $e) {
            return;
        }
    }

    public function calculateMaxLoyaltyRedemption(bool $applyRedemption = false): void
    {
        if (!$this->isLoyaltyEnabled() || !$this->c()->customerId) {
            return;
        }

        if ($this->c()->availableLoyaltyPoints <= 0) {
            $this->c()->pointsToRedeem = 0;
            $this->c()->loyaltyDiscountAmount = 0;
            if (!$applyRedemption) {
                $this->c()->loyaltyPointsRedeemed = 0;
            }
            return;
        }

        try {
            $settings = \Modules\Loyalty\Entities\LoyaltySetting::getForRestaurant($this->c()->restaurant->id);
            if (!$settings || !$settings->isEnabled()) {
                return;
            }

            if (!class_exists('Modules\Kiosk\Services\KioskCartService')) {
                return;
            }

            $kioskService = new \Modules\Kiosk\Services\KioskCartService();
            $cartItemList = $kioskService->getKioskCartSummary($this->c()->shopBranch->id);
            $subtotal = $cartItemList['sub_total'] ?? 0;

            $maxDiscountPercent = $settings->max_discount_percent ?? 0;
            $maxDiscountAmount = ($subtotal * $maxDiscountPercent) / 100;

            $valuePerPoint = $settings->value_per_point ?? 1;
            $maxPointsByDiscount = floor($maxDiscountAmount / $valuePerPoint);

            $pointsToRedeem = min($this->c()->availableLoyaltyPoints, $maxPointsByDiscount);

            if ($settings->min_redeem_points > 0 && $pointsToRedeem > 0) {
                $pointsToRedeem = floor($pointsToRedeem / $settings->min_redeem_points) * $settings->min_redeem_points;
            }

            $discountAmount = $pointsToRedeem * $valuePerPoint;

            if ($applyRedemption) {
                $this->c()->loyaltyPointsRedeemed = $pointsToRedeem;
                $this->c()->loyaltyDiscountAmount = $discountAmount;
            } else {
                $this->c()->loyaltyDiscountAmount = 0;
            }
        } catch (\Exception $e) {
            return;
        }
    }

    public function redeemLoyaltyPoints(): void
    {
        if (!$this->isLoyaltyEnabled() || !$this->c()->customerId) {
            return;
        }

        if ($this->c()->availableLoyaltyPoints <= 0) {
            $this->loadLoyaltyPoints();
        }

        $this->calculateMaxLoyaltyRedemption(true);
    }

    public function removeLoyaltyRedemption(): void
    {
        $this->c()->loyaltyPointsRedeemed = 0;
        $this->c()->loyaltyDiscountAmount = 0;
        $this->c()->pointsToRedeem = 0;
    }

    public function calculateTotalsWithLoyaltyDiscount(array $cartItemList): array
    {
        $discountedSubtotal = $cartItemList['sub_total'];
        $originalSubtotal = $discountedSubtotal;

        if ($this->c()->stampDiscountAmount > 0) {
            $originalSubtotal = $discountedSubtotal + $this->c()->stampDiscountAmount;
        }

        $taxMode = $cartItemList['tax_mode'];
        $preLoyaltySubtotal = $discountedSubtotal;

        if ($this->isLoyaltyEnabled() && $this->c()->loyaltyPointsRedeemed > 0 && $this->c()->loyaltyDiscountAmount > 0) {
            $discountedSubtotal = max(0, $discountedSubtotal - $this->c()->loyaltyDiscountAmount);
        }

        $serviceTotal = 0;
        $chargesBreakdown = [];
        $orderType = $cartItemList['order_type'] ?? 'dine_in';
        $charges = $this->c()->getApplicableKioskCharges($orderType);
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

        $totalTaxAmount = 0;
        $taxBreakdown = [];
        $taxBase = null;

        if ($taxMode === 'order') {
            $taxes = Tax::withoutGlobalScopes()
                ->where('restaurant_id', $this->c()->restaurant->id)
                ->get();

            $includeChargesInTaxBase = $this->c()->restaurant->include_charges_in_tax_base ?? false;
            $taxBase = $includeChargesInTaxBase ? ($discountedSubtotal + $serviceTotal) : $discountedSubtotal;

            foreach ($taxes as $tax) {
                $taxAmount = ($tax->tax_percent / 100) * $taxBase;
                $totalTaxAmount += $taxAmount;
                $taxBreakdown[$tax->tax_name] = [
                    'percent' => $tax->tax_percent,
                    'amount' => round($taxAmount, 2),
                ];
            }
        } else {
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

        $total = $discountedSubtotal + $serviceTotal;
        if ($taxMode === 'order') {
            $total += $totalTaxAmount;
        } else {
            $isInclusive = $this->c()->restaurant->tax_inclusive ?? false;
            if (!$isInclusive) {
                $total += $totalTaxAmount;
            }
        }

        return [
            'subtotal' => $originalSubtotal,
            'discountedSubtotal' => $discountedSubtotal,
            'total' => $total,
            'totalTaxAmount' => $totalTaxAmount,
            'taxBreakdown' => $taxBreakdown,
            'serviceTotal' => $serviceTotal,
            'chargeBreakdown' => $chargesBreakdown,
            'taxBase' => $taxBase,
        ];
    }

    public function loadCustomerStamps(): void
    {
        if (!$this->isStampsEnabledForKiosk() || !$this->c()->customerId) {
            $this->c()->customerStamps = [];
            return;
        }

        try {
            $loyaltyService = app(\Modules\Loyalty\Services\LoyaltyService::class);
            $restaurantId = $this->c()->restaurant->id;

            $allStamps = $loyaltyService->getCustomerStamps($restaurantId, $this->c()->customerId);

            $cartService = new \Modules\Kiosk\Services\KioskCartService();
            $branchId = $this->c()->shopBranch->id ?? null;

            if (!$branchId) {
                $this->c()->customerStamps = [];
                return;
            }

            $cartSummary = $cartService->getKioskCartSummary($branchId);
            $cartMenuItemIds = [];

            $cartItems = $cartSummary['items'] ?? [];
            if ($cartItems instanceof \Illuminate\Support\Collection) {
                $cartItems = $cartItems->toArray();
            }

            if (!empty($cartItems)) {
                foreach ($cartItems as $cartItem) {
                    if (isset($cartItem['menu_item']['id'])) {
                        $cartMenuItemIds[] = $cartItem['menu_item']['id'];
                    }
                }
            }

            $this->c()->customerStamps = collect($allStamps)->filter(function ($stampData) use ($cartMenuItemIds) {
                $rule = $stampData['rule'] ?? null;
                if (!$rule) {
                    return false;
                }
                $ruleMenuItemId = $rule->menu_item_id ?? null;
                return $ruleMenuItemId && in_array($ruleMenuItemId, $cartMenuItemIds);
            })->values()->toArray();
        } catch (\Exception $e) {
            $this->c()->customerStamps = [];
        }
    }

    public function redeemStamps($stampRuleId = null): void
    {
        if (!$this->isStampsEnabledForKiosk() || !$this->c()->customerId) {
            return;
        }

        $stampRuleId = $stampRuleId ?? $this->c()->selectedStampRuleId;
        if (!$stampRuleId) {
            return;
        }

        $this->c()->selectedStampRuleId = $stampRuleId;
        if (!is_array($this->c()->selectedStampRuleIds)) {
            $this->c()->selectedStampRuleIds = [];
        }
        if (in_array($stampRuleId, $this->c()->selectedStampRuleIds, true)) {
            return;
        }
        $this->c()->selectedStampRuleIds[] = $stampRuleId;

        try {
            $stampRule = \Modules\Loyalty\Entities\LoyaltyStampRule::find($stampRuleId);
            if ($stampRule) {
                if ($stampRule->reward_type === 'free_item') {
                    $this->c()->stampDiscountAmount = 0;
                    return;
                }
                $this->applyStampRedemptionsToCart();
            }
        } catch (\Exception $e) {
            $this->c()->stampDiscountAmount = 0;
        }
    }

    public function removeStampRedemption($stampRuleId = null): void
    {
        if (!$this->c()->selectedStampRuleId && empty($this->c()->selectedStampRuleIds)) {
            return;
        }

        $stampRuleId = $stampRuleId ?? $this->c()->selectedStampRuleId;
        if ($stampRuleId && is_array($this->c()->selectedStampRuleIds)) {
            $this->c()->selectedStampRuleIds = array_values(array_filter(
                $this->c()->selectedStampRuleIds,
                fn($id) => (int)$id !== (int)$stampRuleId
            ));
        } else {
            $this->c()->selectedStampRuleIds = [];
        }

        if ($this->c()->selectedStampRuleId && !in_array($this->c()->selectedStampRuleId, $this->c()->selectedStampRuleIds, true)) {
            $this->c()->selectedStampRuleId = null;
        }

        try {
            $this->applyStampRedemptionsToCart();
        } catch (\Exception $e) {
            return;
        }
    }

    protected function applyStampRedemptionsToCart(): void
    {
        if (!class_exists('Modules\Kiosk\Services\KioskCartService')) {
            return;
        }

        $kioskService = new \Modules\Kiosk\Services\KioskCartService();
        $cartSession = $kioskService->getCurrentCartSession($this->c()->shopBranch->id);
        if (!$cartSession) {
            return;
        }

        $cartSession->load(['cartItems.menuItem.taxes', 'cartItems.menuItemVariation', 'cartItems.modifiers', 'branch.restaurant']);

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
                    $taxResult = MenuItem::calculateItemTaxes($basePrice + $modifierPrice, $taxes, $isInclusive);
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

        $this->c()->stampDiscountBreakdown = [];
        $this->c()->stampDiscountAmount = 0;
        $this->c()->stampRedemptionCounts = [];

        $selectedRuleIds = is_array($this->c()->selectedStampRuleIds) ? $this->c()->selectedStampRuleIds : [];
        foreach ($selectedRuleIds as $ruleId) {
            $stampRule = \Modules\Loyalty\Entities\LoyaltyStampRule::find($ruleId);
            if (!$stampRule || $stampRule->reward_type === 'free_item') {
                continue;
            }

            $cartItems = $cartSession->cartItems->filter(function ($item) use ($stampRule) {
                return $item->menu_item_id == $stampRule->menu_item_id;
            });

            if ($cartItems->isEmpty()) {
                continue;
            }

            $stampData = collect($this->c()->customerStamps ?? [])->first(function ($data) use ($ruleId) {
                return isset($data['rule']) && ($data['rule']->id ?? null) == $ruleId;
            });
            $availableStamps = (int)($stampData['available_stamps'] ?? 0);
            $stampsRequired = (int)($stampData['stamps_required'] ?? 0);
            $maxItemsToRedeem = ($stampsRequired > 0) ? intdiv($availableStamps, $stampsRequired) : 0;
            if ($maxItemsToRedeem <= 0) {
                continue;
            }

            $tierMultiplier = 1.00;
            $account = \Modules\Loyalty\Entities\LoyaltyAccount::where('restaurant_id', $this->c()->restaurant->id)
                ->where('customer_id', $this->c()->customerId)
                ->first();
            if ($account && $account->tier_id) {
                $tier = \Modules\Loyalty\Entities\LoyaltyTier::find($account->tier_id);
                if ($tier && $tier->redemption_multiplier > 0) {
                    $tierMultiplier = $tier->redemption_multiplier;
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
                $this->c()->stampDiscountBreakdown[$ruleId] = $totalDiscountApplied;
                $this->c()->stampDiscountAmount += $totalDiscountApplied;
                $this->c()->stampRedemptionCounts[$ruleId] = $itemsRedeemed;
            }
        }

        $cartSessionService = app(CartSessionService::class);
        $reflection = new \ReflectionClass($cartSessionService);
        $method = $reflection->getMethod('updateCartTotals');
        $method->setAccessible(true);
        $method->invoke($cartSessionService, $cartSession);
    }

    public function processLoyaltyRedemptionForOrder($order, $customer): void
    {
        if (!$this->isLoyaltyEnabled() || !$this->c()->loyaltyPointsRedeemed || !$this->c()->loyaltyDiscountAmount || !$customer) {
            return;
        }

        try {
            $loyaltyService = app(\Modules\Loyalty\Services\LoyaltyService::class);
            $result = $loyaltyService->redeemPoints($order, $this->c()->loyaltyPointsRedeemed);
            if (!$result['success']) {
                $order->update([
                    'loyalty_points_redeemed' => 0,
                    'loyalty_discount_amount' => 0,
                ]);
                $this->recalculateOrderTotalsWithoutLoyalty($order);
                $order->refresh();
            }
        } catch (\Exception $e) {
            $order->update([
                'loyalty_points_redeemed' => 0,
                'loyalty_discount_amount' => 0,
            ]);
            $this->recalculateOrderTotalsWithoutLoyalty($order);
            $order->refresh();
        }
    }

    protected function recalculateOrderTotalsWithoutLoyalty($order): void
    {
        if (!class_exists('Modules\Kiosk\Services\KioskCartService')) {
            return;
        }
        $kioskService = new \Modules\Kiosk\Services\KioskCartService();
        $cartItemList = $kioskService->getKioskCartSummary($this->c()->shopBranch->id);

        $orderSubTotal = $cartItemList['sub_total'];
        $taxMode = $cartItemList['tax_mode'];

        $serviceTotal = 0;
        $charges = $this->c()->getApplicableKioskCharges($cartItemList['order_type'] ?? 'dine_in');
        foreach ($charges as $charge) {
            if (is_object($charge) && method_exists($charge, 'getAmount')) {
                $serviceTotal += $charge->getAmount($orderSubTotal);
            }
        }

        $orderTotalTaxAmount = 0;
        if ($taxMode === 'order') {
            $taxes = Tax::withoutGlobalScopes()->where('restaurant_id', $this->c()->restaurant->id)->get();
            $includeChargesInTaxBase = $this->c()->restaurant->include_charges_in_tax_base ?? false;
            $taxBase = $includeChargesInTaxBase ? ($orderSubTotal + $serviceTotal) : $orderSubTotal;
            foreach ($taxes as $tax) {
                $taxAmount = ($tax->tax_percent / 100) * $taxBase;
                $orderTotalTaxAmount += $taxAmount;
            }
        } else {
            $orderTotalTaxAmount = $cartItemList['total_tax_amount'] ?? 0;
        }

        $orderTotal = $orderSubTotal + $serviceTotal + $orderTotalTaxAmount;

        $order->update([
            'total' => round($orderTotal, 2),
            'total_tax_amount' => round($orderTotalTaxAmount, 2),
        ]);
    }

    public function processStampRedemptionForOrder($order, $customer): void
    {
        $stampRuleIds = is_array($this->c()->selectedStampRuleIds) ? $this->c()->selectedStampRuleIds : [];
        if (empty($stampRuleIds) && $this->c()->selectedStampRuleId) {
            $stampRuleIds = [$this->c()->selectedStampRuleId];
        }

        if (!$this->isStampsEnabledForKiosk() || empty($stampRuleIds) || !$customer) {
            return;
        }

        try {
            $preservedLoyaltyPointsRedeemed = $order->loyalty_points_redeemed ?? 0;
            $preservedLoyaltyDiscountAmount = $order->loyalty_discount_amount ?? 0;
            $preservedStampDiscountAmount = $order->stamp_discount_amount ?? $this->c()->stampDiscountAmount;

            $loyaltyService = app(\Modules\Loyalty\Services\LoyaltyService::class);
            foreach ($stampRuleIds as $stampRuleId) {
                $stampRule = \Modules\Loyalty\Entities\LoyaltyStampRule::find($stampRuleId);
                if (!$stampRule) {
                    continue;
                }

                if (in_array($stampRule->reward_type, ['discount_percent', 'discount_amount'])) {
                    $this->finalizeDiscountStampRedemption($order, $stampRuleId);
                    continue;
                }

                $result = $loyaltyService->redeemStamps($order, $stampRuleId);

                if ($result['success']) {
                    $order->refresh();
                    $order->load('items');

                    if ($preservedLoyaltyPointsRedeemed > 0 || $preservedLoyaltyDiscountAmount > 0) {
                        $order->loyalty_points_redeemed = $preservedLoyaltyPointsRedeemed;
                        $order->loyalty_discount_amount = $preservedLoyaltyDiscountAmount;
                        $order->save();
                    }

                    if ($preservedStampDiscountAmount > 0) {
                        $order->stamp_discount_amount = $preservedStampDiscountAmount;
                        $order->save();
                    }

                    $this->c()->stampDiscountAmount = $order->stamp_discount_amount ?? 0;
                    $this->recalculateOrderTotalAfterStampRedemption($order);
                }
            }
        } catch (\Exception $e) {
            return;
        }
    }

    protected function finalizeDiscountStampRedemption($order, int $stampRuleId): void
    {
        try {
            $stampRule = \Modules\Loyalty\Entities\LoyaltyStampRule::find($stampRuleId);
            if (!$stampRule) {
                return;
            }

            $itemsRedeemed = (int)($this->c()->stampRedemptionCounts[$stampRuleId] ?? 0);
            if ($itemsRedeemed <= 0) {
                return;
            }

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

            $order->items()
                ->where('menu_item_id', $stampRule->menu_item_id)
                ->where(function ($q) {
                    $q->whereNull('is_free_item_from_stamp')
                        ->orWhere('is_free_item_from_stamp', false);
                })
                ->update(['stamp_rule_id' => $stampRuleId]);

            $customerStamp = \Modules\Loyalty\Entities\CustomerStamp::getOrCreate(
                $this->c()->restaurant->id,
                $order->customer_id,
                $stampRuleId
            );

            $stampsToRedeem = $itemsToRedeem * $stampsRequired;
            $customerStamp->stamps_redeemed += $stampsToRedeem;
            $customerStamp->last_redeemed_at = now();
            $customerStamp->save();

            for ($i = 0; $i < $itemsToRedeem; $i++) {
                \Modules\Loyalty\Entities\LoyaltyStampTransaction::create([
                    'restaurant_id' => $this->c()->restaurant->id,
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

            if ($this->c()->stampDiscountAmount > 0) {
                $order->stamp_discount_amount = $this->c()->stampDiscountAmount;
                $order->save();
            }
        } catch (\Exception $e) {
            return;
        }
    }

    protected function recalculateOrderTotalAfterStampRedemption($order): void
    {
        try {
            $order->refresh();
            $order->load(['items', 'taxes.tax', 'charges.charge']);

            $correctSubTotal = (float)($order->items->sum('amount') ?? 0);
            $discountedSubTotal = (float)$correctSubTotal;
            $discountedSubTotal -= (float)($order->discount_amount ?? 0);
            $discountedSubTotal -= (float)($order->loyalty_discount_amount ?? 0);

            $serviceTotal = 0.0;
            if ($order->charges && $order->charges->count() > 0) {
                foreach ($order->charges as $chargeRelation) {
                    $charge = $chargeRelation->charge;
                    if ($charge) {
                        $serviceTotal += $charge->getAmount(max(0, (float)$discountedSubTotal));
                    }
                }
            }

            $includeChargesInTaxBase = $this->c()->restaurant->include_charges_in_tax_base ?? false;
            $taxBase = $includeChargesInTaxBase ? (max(0, (float)$discountedSubTotal) + $serviceTotal) : max(0, (float)$discountedSubTotal);
            $correctTaxAmount = 0.0;
            $taxMode = $order->tax_mode ?? 'order';

            if ($taxMode === 'order') {
                $taxes = Tax::withoutGlobalScopes()
                    ->where('restaurant_id', $this->c()->restaurant->id)
                    ->get();

                foreach ($taxes as $tax) {
                    if (isset($tax->tax_percent)) {
                        $taxPercent = (float)$tax->tax_percent;
                        $taxAmount = ($taxPercent / 100.0) * (float)$taxBase;
                        $correctTaxAmount += $taxAmount;
                    }
                }
                $correctTaxAmount = round($correctTaxAmount, 2);
            } else {
                $correctTaxAmount = (float)($order->items->sum('tax_amount') ?? 0);
            }

            $correctTotal = max(0, (float)$discountedSubTotal);
            $correctTotal += (float)$serviceTotal;

            if ($taxMode === 'order') {
                $correctTotal += (float)$correctTaxAmount;
            } else {
                $isInclusive = ($this->c()->restaurant->tax_inclusive ?? false);
                if (!$isInclusive && $correctTaxAmount > 0) {
                    $correctTotal += (float)$correctTaxAmount;
                }
            }

            $correctSubTotal = round($correctSubTotal, 2);
            $correctTotal = round($correctTotal, 2);
            $correctTaxAmount = round($correctTaxAmount, 2);

            $updateData = [
                'sub_total' => $correctSubTotal,
                'total' => $correctTotal,
                'total_tax_amount' => $correctTaxAmount,
                'tax_base' => $taxBase,
            ];

            if ($order->loyalty_points_redeemed > 0) {
                $updateData['loyalty_points_redeemed'] = $order->loyalty_points_redeemed;
            }
            if ($order->loyalty_discount_amount > 0) {
                $updateData['loyalty_discount_amount'] = $order->loyalty_discount_amount;
            }

            $order->update($updateData);
            $order->refresh();
        } catch (\Exception $e) {
            return;
        }
    }

    public function loadCustomerAndLoyaltyData(): void
    {
        if ($this->isPointsEnabledForKiosk() && $this->c()->customerId) {
            $this->loadLoyaltyPoints();
        }

        if ($this->isStampsEnabledForKiosk() && $this->c()->customerId) {
            $this->loadCustomerStamps();
        }
    }
}
