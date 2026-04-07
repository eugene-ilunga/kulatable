<?php

namespace Modules\Loyalty\Services;

use App\Models\Kot;
use App\Models\KotItem;
use App\Models\Order;
use App\Models\OrderCharge;
use App\Models\OrderItem;
use App\Models\OrderTax;
use App\Models\OrderType;
use App\Models\RestaurantCharge;
use App\Models\Tax;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PosLoyaltyHandler
{
    protected $pos;

    public function __construct($pos)
    {
        $this->pos = $pos;
    }

    public function &__get($name)
    {
        return $this->pos->$name;
    }

    public function __set($name, $value)
    {
        $this->pos->$name = $value;
    }

    public function __call($method, $arguments)
    {
        return $this->pos->$method(...$arguments);
    }

    public function isLoyaltyEnabled()
    {
        $restaurantId = restaurant()->id ?? null;
        return self::isLoyaltyProgramEnabled($restaurantId);
    }

    public static function isLoyaltyProgramEnabled($restaurantId = null): bool
    {
        if (!$restaurantId) {
            return false;
        }

        $settings = \Modules\Loyalty\Entities\LoyaltySetting::getForRestaurant($restaurantId);
        return (bool)($settings->enabled ?? false);
    }

    /**
     * Check if points are enabled for POS platform
     */
    public function isPointsEnabledForPOS()
    {
        if (!$this->isLoyaltyEnabled()) {
            return false;
        }


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
                return (bool) $settings->enable_stamps_for_pos;
            }

            return (bool) ($settings->enable_for_pos ?? true);
        }
    }

    /**
     * Reset loyalty redemption (stub method if trait doesn't exist)
     */
    public function resetLoyaltyRedemption()
    {

        $traits = class_uses_recursive(static::class);

        if (in_array(\Modules\Loyalty\Traits\HasLoyaltyIntegration::class, $traits)) {
            return;
        }

        $this->loyaltyPointsRedeemed = 0;
        $this->loyaltyDiscountAmount = 0;
        $this->availableLoyaltyPoints = 0;
        $this->pointsToRedeem = 0;
        $this->maxRedeemablePoints = 0;
        $this->minRedeemPoints = 0;
        $this->showLoyaltyRedemptionModal = false;
    }

    public function handleCustomerSelected(): void
    {

        $this->resetLoyaltyRedemption();
        $this->checkLoyaltyPointsOnCustomerSelect();
        $this->loadCustomerStamps();
    }

    public function unsetLoyaltyOrderRule($key, $menuItemId)
    {
        $keyParts = explode('_', $key);
        if (!isset($keyParts[2])) {
            return;
        }

        $stampRuleId = $keyParts[2];
        $stampRule = \Modules\Loyalty\Entities\LoyaltyStampRule::find($stampRuleId);

        if (!$stampRule || $stampRule->menu_item_id != $menuItemId) {
            return;
        }

        if ($this->selectedStampRuleId == $stampRuleId) {
            $this->selectedStampRuleId = null;
            $this->stampDiscountAmount = 0;
        }

        unset($this->orderItemList[$key]);
        unset($this->orderItemQty[$key]);
        unset($this->orderItemAmount[$key]);
        unset($this->orderItemVariation[$key]);
        unset($this->itemModifiersSelected[$key]);
        unset($this->itemNotes[$key]);
        unset($this->orderItemModifiersPrice[$key]);
        unset($this->orderItemTaxDetails[$key]);
    }

    public function freeLoyaltyAmountRedeem($orderItem)
    {
        if (!$orderItem->relationLoaded('order')) {
            $orderItem->load('order.branch');
        }

        if (!$orderItem->order) {
            return;
        }

        $restaurantId = $orderItem->order->branch->restaurant_id
            ?? restaurant()->id
            ?? null;

        if (!$restaurantId) {
            return;
        }

        $stampRules = \Modules\Loyalty\Entities\LoyaltyStampRule::where('menu_item_id', $orderItem->menu_item_id)
            ->where('restaurant_id', $restaurantId)
            ->pluck('id')
            ->toArray();

        if (empty($stampRules)) {
            return;
        }

        $relatedFreeItems = \App\Models\OrderItem::where('order_id', $orderItem->order_id)
            ->where('is_free_item_from_stamp', true)
            ->whereIn('stamp_rule_id', $stampRules)
            ->get();

        foreach ($relatedFreeItems as $freeItem) {
            if ($this->selectedStampRuleId == $freeItem->stamp_rule_id) {
                $this->selectedStampRuleId = null;
                $this->stampDiscountAmount = 0;
            }

            $freeItem->delete();
        }
    }

    public function recalculateLoyaltyDiscount()
    {
        $this->loyaltyDiscountAmount = 0;

        $settings = \Modules\Loyalty\Entities\LoyaltySetting::getForRestaurant(restaurant()->id);

        if (!$settings || !$this->subTotal || $this->loyaltyPointsRedeemed <= 0) {
            return;
        }

        $valuePerPoint = $settings->value_per_point ?? 1;
        $pointsDiscount = $this->loyaltyPointsRedeemed * $valuePerPoint;

        $maxDiscountPercent = $settings->max_discount_percent ?? 0;
        $maxDiscountAmount = ($this->subTotal * $maxDiscountPercent) / 100;

        $this->loyaltyDiscountAmount = min($pointsDiscount, $maxDiscountAmount);

        if ($this->loyaltyDiscountAmount > 0) {
            $this->total -= $this->loyaltyDiscountAmount;
        }
    }

    public function getStampRuleId($menuItemId)
    {
        $restaurantId = restaurant()->id;

        $stampRule = \Modules\Loyalty\Entities\LoyaltyStampRule::getRuleForMenuItem(
            $restaurantId,
            $menuItemId
        );

        if (
            $stampRule &&
            $stampRule->is_active &&
            $stampRule->id == $this->selectedStampRuleId
        ) {
            return $stampRule->id;
        }

        return null;
    }

    public function resolveStampDataForItem(array $data)
    {
        if ($data['stampDataFromOrderItem']) {
            return $data;
        }

        if ($data['isFreeItemFromStamp']) {
            $data['itemAmount'] = 0;
            return $data;
        }

        $data = $this->resolveSelectedStampDiscount($data);

        if (!$data['stampRuleId']) {
            $data = $this->resolveStampViaHandler($data);
        }

        return $data;
    }

    public function resolveSelectedStampDiscount(array $data)
    {
        if (!$this->selectedStampRuleId) {
            return $data;
        }

        $expectedAmount = $data['itemPrice'] * $data['quantity'];

        if ($expectedAmount <= $data['itemAmount'] + 0.01) {
            return $data;
        }

        $stampRule = \Modules\Loyalty\Entities\LoyaltyStampRule::getRuleForMenuItem(
            restaurant()->id,
            $data['menuItemId']
        );

        if ($stampRule && $stampRule->is_active && $stampRule->id == $this->selectedStampRuleId) {
            $data['stampRuleId'] = $stampRule->id;
        }

        return $data;
    }

    public function resolveStampViaHandler(array $data)
    {
        [$ruleId, $discount, $isDiscounted] =
            $this->resolveStampDiscountForItem(
                $data['menuItemId'],
                (float)($data['itemPrice'] * $data['quantity']),
                (float)$data['itemAmount']
            );

        if ($ruleId) {
            $data['stampRuleId'] = $ruleId;
            $data['discountAmount'] = $discount;
            $data['isDiscounted'] = $isDiscounted;
        }

        return $data;
    }

    public function applyStampRuleDiscount(array $data)
    {
        if (
            !$this->selectedStampRuleId ||
            $data['isFreeItemFromStamp'] ||
            $data['stampDataFromOrderItem']
        ) {
            return $data;
        }

        $stampRule = \Modules\Loyalty\Entities\LoyaltyStampRule::find($this->selectedStampRuleId);

        if (
            !$stampRule ||
            $stampRule->menu_item_id != $data['menuItemId'] ||
            !in_array($stampRule->reward_type, ['discount_percent', 'discount_amount'])
        ) {
            return $data;
        }

        $quantity = (int)($data['quantity'] ?? 0);
        if ($quantity <= 0) {
            return $data;
        }

        $originalAmount = $data['itemPrice'] * $quantity;

        // Limit discount to available stamps (apply only to eligible quantity)
        $eligibleQty = $quantity;
        if ($this->customerId) {
            $restaurantId = restaurant()->id;
            $loyaltyService = app(\Modules\Loyalty\Services\LoyaltyService::class);
            $availableStamps = $loyaltyService->getAvailableStamps($restaurantId, $this->customerId, $stampRule->id);
            $eligibleQty = (int)floor($availableStamps / max(1, (int)$stampRule->stamps_required));
            $eligibleQty = min($quantity, max(0, $eligibleQty));
        }

        if ($eligibleQty <= 0) {
            return $data;
        }

        // Apply tier redemption multiplier for discount rewards
        $tierMultiplier = 1.00;
        if ($this->customerId) {
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
                // Ignore tier errors, use default multiplier
            }
        }

        $perUnitDiscount = $stampRule->reward_type === 'discount_percent'
            ? (($data['itemPrice'] * $stampRule->reward_value) / 100) * $tierMultiplier
            : min($stampRule->reward_value * $tierMultiplier, $data['itemPrice']);

        $discount = $perUnitDiscount * $eligibleQty;

        $data['discountAmount'] = round($discount, 2);
        $data['itemAmount'] = round(max(0, $originalAmount - $discount), 2);
        $data['isDiscounted'] = $data['discountAmount'] > 0;
        $data['stampRuleId'] = $stampRule->id;

        return $data;
    }

    public function preserveStampFromDraftOrderItem(array $data, $order, array $item)
    {
        if (!$order || !str_contains($data['key'], 'order_item_')) {
            return $data;
        }

        $keyParts = explode('_', trim($data['key'], '"'));

        if (count($keyParts) < 3) {
            return $data;
        }

        $orderItemId = (int)($keyParts[2] ?? 0);

        if ($orderItemId <= 0) {
            return $data;
        }

        if (!$order->relationLoaded('items')) {
            $order->load('items');
        }

        $orderItem = $order->items->firstWhere('id', $orderItemId);

        if (!$orderItem) {
            return $data;
        }

        $data['stampDataFromOrderItem'] = true;
        $data['stampRuleId'] = $orderItem->stamp_rule_id;
        $data['isFreeItemFromStamp'] = (bool)$orderItem->is_free_item_from_stamp;
        $data['itemAmount'] = (float)$orderItem->amount;

        if ($data['isFreeItemFromStamp']) {
            $data['itemAmount'] = 0;
        }

        $expected = ($orderItem->price ?? $data['itemPrice']) * ($orderItem->quantity ?? $data['quantity']);
        $data['discountAmount'] = max(0, round($expected - $data['itemAmount'], 2));
        $data['isDiscounted'] = $data['discountAmount'] > 0;

        return $data;
    }

    public function handleStampRedemptionForOrder($order)
    {
        if (
            !$this->isStampsEnabledForPOS() ||
            !$order
        ) {
            return;
        }

        if (!$order->relationLoaded('items')) {
            $order->load('items');
        }

        $stampRuleIds = [];

        foreach ($order->items as $orderItem) {
            if ($orderItem->stamp_rule_id) {
                $stampRuleIds[$orderItem->stamp_rule_id] = true;
            }
        }

        if ($this->selectedStampRuleId) {
            $stampRuleIds[$this->selectedStampRuleId] = true;
        }

        foreach (array_keys($stampRuleIds) as $stampRuleId) {
            if (!$stampRuleId) {
                continue;
            }

            $this->redeemStampsForAllEligibleItems($order, $stampRuleId);
        }

        $order->refresh();
        $this->recalculateOrderTotalAfterStampRedemption($order);
        $order->refresh();
    }

    public function handleLoyaltyPointsRedemptionForOrder($order)
    {
        if (
            !$this->isPointsEnabledForPOS() ||
            $this->loyaltyPointsRedeemed <= 0 ||
            $this->loyaltyDiscountAmount <= 0
        ) {
            return;
        }

        $loyaltyService = app(\Modules\Loyalty\Services\LoyaltyService::class);

        $result = $loyaltyService->redeemPoints($order, $this->loyaltyPointsRedeemed);

        if (!$result['success']) {
            return;
        }

        $order->refresh();
        $order->load(['taxes', 'charges.charge', 'items']);
    }

    public function resolveStampDiscountForItem(int $menuItemId, float $expectedAmount, float $actualAmount): array
    {
        if ($expectedAmount <= $actualAmount + 0.01) {
            return [null, 0.0, false];
        }

        $restaurantId = restaurant()->id;
        $stampRule = \Modules\Loyalty\Entities\LoyaltyStampRule::getRuleForMenuItem($restaurantId, $menuItemId);
        if (!$stampRule || !$stampRule->is_active) {
            return [null, 0.0, false];
        }

        if (!in_array($stampRule->reward_type, ['discount_percent', 'discount_amount'])) {
            return [null, 0.0, false];
        }

        $discountAmount = round(max(0, $expectedAmount - $actualAmount), 2);
        if ($discountAmount <= 0) {
            return [null, 0.0, false];
        }

        return [$stampRule->id, $discountAmount, true];
    }

    /**
     * Check loyalty points on customer select (stub method if trait doesn't exist)
     */
    public function checkLoyaltyPointsOnCustomerSelect()
    {
        if (!$this->customerId) {
            return;
        }

        if (count($this->orderItemList) == 0) {
            return;
        }

        $this->subTotal = $this->orderItemAmount ? array_sum($this->orderItemAmount) : 0;

        if ($this->subTotal <= 0) {
            return;
        }

        $this->openLoyaltyRedemptionModal();
    }

    /**
     * Update loyalty values (stub method if trait doesn't exist)
     */
    public function updateLoyaltyValues()
    {
        if (!$this->isLoyaltyEnabled() || !$this->customerId) {
            return;
        }

        try {
            $loyaltyService = app(\Modules\Loyalty\Services\LoyaltyService::class);
            $restaurantId = restaurant()->id;
            $this->availableLoyaltyPoints = $loyaltyService->getAvailablePoints($restaurantId, $this->customerId);

            $settings = \Modules\Loyalty\Entities\LoyaltySetting::getForRestaurant($restaurantId);
            if (!$settings) {
                return;
            }

            $loyaltyType = $settings->loyalty_type ?? 'points';
            $pointsEnabled = in_array($loyaltyType, ['points', 'both']) && ($settings->enable_points ?? true);

            if ($settings->isEnabled() && $pointsEnabled) {
                $valuePerPoint = $settings->value_per_point ?? 1;
                $this->minRedeemPoints = $settings->min_redeem_points ?? 0;

                $this->loyaltyPointsValue = $this->availableLoyaltyPoints * $valuePerPoint;

                $subtotal = $this->subTotal ?? 0;
                $maxDiscountToday = 0;
                if ($subtotal > 0) {
                    $maxDiscountToday = $subtotal * ($settings->max_discount_percent / 100);
                }
                $this->maxLoyaltyDiscount = $maxDiscountToday;

                $maxPointsFromAvailable = 0;
                if ($this->minRedeemPoints > 0 && $this->availableLoyaltyPoints >= $this->minRedeemPoints) {
                    $maxPointsFromAvailable = floor($this->availableLoyaltyPoints / $this->minRedeemPoints) * $this->minRedeemPoints;
                }

                $maxPointsFromDiscount = 0;
                if ($maxDiscountToday > 0 && $this->minRedeemPoints > 0) {
                    $maxPointsFromDiscountValue = floor($maxDiscountToday / $valuePerPoint);
                    if ($maxPointsFromDiscountValue >= $this->minRedeemPoints) {
                        $maxPointsFromDiscount = floor($maxPointsFromDiscountValue / $this->minRedeemPoints) * $this->minRedeemPoints;
                    }
                }

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
        } catch (\Exception $e) {
            // Intentionally silent.
        }
    }

    /**
     * Load loyalty data for order (stub method if trait doesn't exist)
     */
    public function loadLoyaltyDataForOrder()
    {
        $traits = class_uses_recursive(static::class);
        if (in_array(\Modules\Loyalty\Traits\HasLoyaltyIntegration::class, $traits)) {
            // Trait exists and is used, it will handle this
            return;
        }
    }

    /**
     * Open loyalty redemption modal and load loyalty values
     */
    public function openLoyaltyRedemptionModal()
    {
        if ($this->isLoyaltyEnabled() && $this->customerId) {
            // Check if there are items in cart
            if (count($this->orderItemList) == 0 || $this->subTotal <= 0) {
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
            if ($this->customerId) {
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

            // Show user-friendly error message
            $errorMsg = function_exists('__') && function_exists('module_enabled') && module_enabled('Loyalty')
                ?? __('loyalty::app.failedToRedeemPoints');

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
    public function recalculateOrderTotalAfterStampRedemption($order)
    {
        // Reload order with all relationships to get accurate item amounts
        $order->refresh();
        $order->load(['items', 'taxes.tax', 'kot.items']);

        // Calculate subtotal - for KOT orders, use KOT items; otherwise use order items
        $correctSubTotal = 0.0;
        if ($order->status === 'kot' && $order->kot && $order->kot->count() > 0) {
            // For KOT orders, calculate from KOT items
            foreach ($order->kot as $kot) {
                foreach ($kot->items as $kotItem) {
                    if ($kotItem->amount !== null) {
                        $correctSubTotal += (float)$kotItem->amount;
                        continue;
                    }

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
            $taxesToUse = collect();
            if ($order->taxes && $order->taxes->count() > 0) {
                $taxesToUse = $order->taxes->map(fn($orderTax) => $orderTax->tax)->filter();
            } elseif (!empty($this->taxes)) {
                $taxesToUse = collect($this->taxes)->filter();
            } else {
                $taxesToUse = Tax::all();
            }

            foreach ($taxesToUse as $tax) {
                if ($tax && isset($tax->tax_percent)) {
                    $taxPercent = (float)$tax->tax_percent;
                    $taxAmount = ($taxPercent / 100.0) * (float)$taxBase;
                    $correctTaxAmount += $taxAmount;
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

        // Compute stamp discount from items if missing on order
        $computedStampDiscount = 0.0;
        if ($order->status === 'kot' && $order->kot && $order->kot->count() > 0) {
            foreach ($order->kot as $kot) {
                foreach ($kot->items as $kotItem) {
                    if (($kotItem->is_free_item_from_stamp ?? false)) {
                        continue;
                    }
                    if (($kotItem->discount_amount ?? 0) > 0 && (($kotItem->stamp_rule_id ?? null) || ($kotItem->is_discounted ?? false))) {
                        $computedStampDiscount += (float)$kotItem->discount_amount;
                    } elseif ($kotItem->stamp_rule_id) {
                        $expected = (float)($kotItem->price ?? 0) * (int)($kotItem->quantity ?? 1);
                        $actual = (float)($kotItem->amount ?? $expected);
                        $computedStampDiscount += max(0, round($expected - $actual, 2));
                    }
                }
            }
        } else {
            foreach ($order->items as $orderItem) {
                if (($orderItem->is_free_item_from_stamp ?? false)) {
                    continue;
                }
                if (($orderItem->discount_amount ?? 0) > 0 && (($orderItem->stamp_rule_id ?? null) || ($orderItem->is_discounted ?? false))) {
                    $computedStampDiscount += (float)$orderItem->discount_amount;
                } elseif ($orderItem->stamp_rule_id) {
                    $expected = (float)($orderItem->price ?? 0) * (int)($orderItem->quantity ?? 1);
                    $actual = (float)($orderItem->amount ?? $expected);
                    $computedStampDiscount += max(0, round($expected - $actual, 2));
                }
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
            'tax_base' => round($taxBase, 2),
            'tax_mode' => $taxMode,
        ];

        // Preserve loyalty fields (do not wipe existing values)
        $updateData['loyalty_points_redeemed'] = (int)($order->loyalty_points_redeemed ?? 0);
        $updateData['loyalty_discount_amount'] = (float)($order->loyalty_discount_amount ?? 0);
        $updateData['stamp_discount_amount'] = $order->stamp_discount_amount !== null
            ? (float)$order->stamp_discount_amount
            : round($computedStampDiscount, 2);

        // Update order with all calculated values (preserving loyalty values)
        $order->update($updateData);

        // Refresh order
        $order->refresh();
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

        $loyaltyService = app(\Modules\Loyalty\Services\LoyaltyService::class);
        $restaurantId = restaurant()->id;

        // Get customer stamps with rules
        $this->customerStamps = $loyaltyService->getCustomerStamps($restaurantId, $this->customerId);
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
                    $this->customerId
                ) {
                    $loyaltyService = app(\Modules\Loyalty\Services\LoyaltyService::class);
                    $account = $loyaltyService->getOrCreateAccount(restaurant()->id, $this->customerId);
                    if ($account && $account->tier_id) {
                        $tier = \Modules\Loyalty\Entities\LoyaltyTier::find($account->tier_id);
                        if ($tier && $tier->redemption_multiplier > 0) {
                            $tierMultiplier = $tier->redemption_multiplier;
                        }
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
    public function checkAndAutoRedeemStampsForItem($itemKey)
    {
        // CRITICAL: Only auto-redeem if customer is selected FIRST
        // If customer is not selected, don't auto-redeem (prevents issues when items added before customer)
        if (!$this->customerId || !$this->isLoyaltyEnabled()) {
            return;
        }

        // Check if stamps are enabled for POS platform
        if (!$this->isStampsEnabledForPOS()) {
            return;
        }


        if (!module_enabled('Loyalty') || !module_enabled('Loyalty')) {
            return;
        }

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

        if ($availableStamps >= $stampRule->stamps_required) {
            // Customer has enough stamps - auto-redeem
            $this->selectedStampRuleId = $stampRule->id;
            $eligibleQty = (int)floor($availableStamps / max(1, (int)$stampRule->stamps_required));

            // Calculate reward value for preview
            if ($stampRule->reward_type === 'free_item') {
                // For free item, add it to cart immediately
                if ($stampRule->rewardMenuItem) {
                    $freeItemKey = 'free_stamp_' . $stampRule->id;
                    $existingFreeKeys = [];
                    foreach ($this->orderItemList as $existingKey => $existingItem) {
                        if (strpos($existingKey, $freeItemKey) === 0) {
                            $existingFreeKeys[] = $existingKey;
                        }
                    }
                    if (!empty($existingFreeKeys)) {
                        // Reuse the first existing key to avoid duplicates
                        $freeItemKey = $existingFreeKeys[0];
                    }
                    $targetFreeQty = min($eligibleQty, (int)($this->orderItemQty[$itemKey] ?? 1));

                    if ($targetFreeQty > 0) {
                        // Create or update a single free-item line with quantity
                        $this->orderItemList[$freeItemKey] = $stampRule->rewardMenuItem;
                        $this->orderItemQty[$freeItemKey] = $targetFreeQty;
                        $this->orderItemAmount[$freeItemKey] = 0; // Free item - no charge

                        // Set variation if specified in stamp rule
                        if ($stampRule->reward_menu_item_variation_id && $stampRule->rewardMenuItemVariation) {
                            $this->orderItemVariation[$freeItemKey] = $stampRule->rewardMenuItemVariation;
                        } else {
                            $this->orderItemVariation[$freeItemKey] = null;
                        }

                        // Mark as free item for display
                        if (!isset($this->itemNotes[$freeItemKey])) {
                            $this->itemNotes[$freeItemKey] = __('loyalty::app.freeItemFromStamp');
                        }

                        // Remove any extra duplicate free item keys for the same rule
                        foreach ($existingFreeKeys as $dupKey) {
                            if ($dupKey === $freeItemKey) {
                                continue;
                            }
                            unset($this->orderItemList[$dupKey]);
                            unset($this->orderItemQty[$dupKey]);
                            unset($this->orderItemAmount[$dupKey]);
                            unset($this->orderItemVariation[$dupKey]);
                            unset($this->itemNotes[$dupKey]);
                            unset($this->itemModifiersSelected[$dupKey]);
                            unset($this->orderItemModifiersPrice[$dupKey]);
                            unset($this->orderItemTaxDetails[$dupKey]);
                        }
                    } else {
                        // Remove free item line if not eligible anymore
                        unset($this->orderItemList[$freeItemKey]);
                        unset($this->orderItemQty[$freeItemKey]);
                        unset($this->orderItemAmount[$freeItemKey]);
                        unset($this->orderItemVariation[$freeItemKey]);
                        unset($this->itemNotes[$freeItemKey]);

                        foreach ($existingFreeKeys as $dupKey) {
                            unset($this->orderItemList[$dupKey]);
                            unset($this->orderItemQty[$dupKey]);
                            unset($this->orderItemAmount[$dupKey]);
                            unset($this->orderItemVariation[$dupKey]);
                            unset($this->itemNotes[$dupKey]);
                            unset($this->itemModifiersSelected[$dupKey]);
                            unset($this->orderItemModifiersPrice[$dupKey]);
                            unset($this->orderItemTaxDetails[$dupKey]);
                        }
                    }
                }

                $this->stampDiscountAmount = 0; // Free item has no discount amount
            } else {
                // For discount rewards, apply discount to the item that triggered the redemption
                // Apply tier redemption multiplier if customer has a tier
                $tierMultiplier = 1.00;
                if ($this->customerId) {
                    $loyaltyService = app(\Modules\Loyalty\Services\LoyaltyService::class);
                    $account = $loyaltyService->getOrCreateAccount(restaurant()->id, $this->customerId);

                    if ($account && $account->tier_id) {
                        $tier = \Modules\Loyalty\Entities\LoyaltyTier::find($account->tier_id);
                        if ($tier && $tier->redemption_multiplier > 0) {
                            $tierMultiplier = $tier->redemption_multiplier;
                        }
                    }
                }

                // Apply discount to the specific item that triggered the stamp redemption
                if (isset($this->orderItemAmount[$itemKey]) && $this->orderItemAmount[$itemKey] > 0) {
                    // Calculate discount for this specific item
                    $qty = (int)($this->orderItemQty[$itemKey] ?? 1);
                    $basePrice = isset($this->orderItemVariation[$itemKey])
                        ? $this->orderItemVariation[$itemKey]->price
                        : ($this->orderItemList[$itemKey]->price ?? 0);
                    $modifierPrice = $this->orderItemModifiersPrice[$itemKey] ?? 0;
                    $unitPrice = $basePrice + $modifierPrice;
                    $itemAmount = $unitPrice * $qty;
                    $itemDiscount = 0;
                    $eligibleItems = min($qty, max(0, $eligibleQty));
                    if ($eligibleItems <= 0) {
                        return;
                    }

                    if ($stampRule->reward_type === 'discount_percent') {
                        // Apply percentage discount to this item
                        $baseItemDiscount = (($unitPrice * $stampRule->reward_value) / 100) * $eligibleItems;
                        // Apply tier multiplier to item discount
                        $itemDiscount = $baseItemDiscount * $tierMultiplier;
                    } elseif ($stampRule->reward_type === 'discount_amount') {
                        // For fixed discount amount, calculate the discount value with tier multiplier first
                        $discountValue = $stampRule->reward_value * $tierMultiplier;
                        $itemDiscount = min($discountValue, $unitPrice) * $eligibleItems;
                    }

                    // Round discount to 2 decimal places to prevent precision issues
                    $itemDiscount = round($itemDiscount, 2);

                    // Reduce item amount by discount and round to 2 decimal places
                    $this->orderItemAmount[$itemKey] = round(max(0, $itemAmount - $itemDiscount), 2);

                    // Update stampDiscountAmount to reflect the actual discount applied to the item
                    // This ensures the order will have the correct stamp_discount_amount when saved
                    $this->stampDiscountAmount = $itemDiscount;

                    // Add note to item indicating discount was applied
                    $discountNote = '';
                    $restaurant = restaurant();
                    $currencyId = $restaurant->currency_id ?? null;
                    if ($stampRule->reward_type === 'discount_percent') {
                        $formattedAmount = $currencyId ? currency_format($itemDiscount, $currencyId) : number_format($itemDiscount, 2);
                        $discountNote = __('app.stampDiscountApplied', [
                            'percent' => $stampRule->reward_value,
                            'amount' => $formattedAmount
                        ]);
                    } else {
                        $formattedAmount = $currencyId ? currency_format($itemDiscount, $currencyId) : number_format($itemDiscount, 2);
                        $discountNote = __('loyalty::app.stampDiscountAppliedAmount', [
                            'amount' => $formattedAmount
                        ]);
                    }

                    if (!isset($this->itemNotes[$itemKey])) {
                        $this->itemNotes[$itemKey] = $discountNote;
                    } else {
                        $this->itemNotes[$itemKey] .= ' | ' . $discountNote;
                    }
                }

                // Recalculate total to apply the discount immediately
                $this->calculateTotal();
            }

            // Prepare reward message
            $rewardMessage = '';
            if ($stampRule->reward_type === 'free_item') {
                $rewardMessage = $stampRule->rewardMenuItem
                    ? $stampRule->rewardMenuItem->item_name
                    : __('loyalty::app.freeItem');
            } else {
                $rewardMessage = __('loyalty::app.discount');
            }

            $itemName = $stampRule->menuItem ? $stampRule->menuItem->item_name : __('loyalty::app.unknownItem');

            // Show notification
            $this->alert('success', __('loyalty::app.stampAutoRedeemed', [
                'item' => $itemName,
                'reward' => $rewardMessage
            ]), [
                'toast' => true,
                'position' => 'top-end',
                'timer' => 5000,
            ]);
        }
    }

    public function redeemLoyaltyPointsAfterOrderCreation(\App\Models\Order $order, string $status): void
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

                \Illuminate\Support\Facades\DB::table('orders')->where('id', $order->id)->update($updateData);

                $order->refresh();

                // CRITICAL: Update component's total to match database
                $this->total = $order->total;
                $this->subTotal = $order->sub_total;
                $this->totalTaxAmount = $order->total_tax_amount;

                // FINAL VERIFICATION: Check what's actually in the database
            } else {
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


    public function redeemStampsAfterOrderCreation(\App\Models\Order $order, string $status): void
    {
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
            $this->alert('error', $result['message'], [
                'toast' => true,
                'position' => 'top-end',
            ]);
        }
    }

    public function applyStampDiscountToOrderData(array &$orderData): void
    {
        if ($this->selectedStampRuleId && $this->stampDiscountAmount > 0 && module_enabled('Loyalty')) {
            $stampRule = \Modules\Loyalty\Entities\LoyaltyStampRule::find($this->selectedStampRuleId);
            if ($stampRule && in_array($stampRule->reward_type, ['discount_percent', 'discount_amount'])) {
                // Discount was already applied to item, so save it to order
                $orderData['stamp_discount_amount'] = $this->stampDiscountAmount;
            }
        }
    }


    public function ensureTotalsIncludeLoyaltyBeforeUpdate(): void
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

    public function handleExistingOrderLoyaltyRedemption(\App\Models\Order $order, int $existingRedeemedPoints = 0): void
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

                $loyaltyService = app(\Modules\Loyalty\Services\LoyaltyService::class);
                $result = $loyaltyService->redeemPoints($order, $this->loyaltyPointsRedeemed);

                if (!$result['success']) {
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

                    $serviceTotal = 0;
                    if ($order->charges && $order->charges->count() > 0) {
                        foreach ($order->charges as $chargeRelation) {
                            if ($chargeRelation->charge) {
                                $serviceTotal += $chargeRelation->charge->getAmount($discountedBase);
                            }
                        }
                    }

                    $includeChargesInTaxBase = restaurant()->include_charges_in_tax_base ?? true;
                    $taxBase = $includeChargesInTaxBase ? ($discountedBase + $serviceTotal) : $discountedBase;

                    $correctTaxAmount = 0;
                    if ($order->tax_mode === 'order' && $order->taxes && $order->taxes->count() > 0) {
                        foreach ($order->taxes as $tax) {
                            $correctTaxAmount += ($tax->tax_percent / 100) * $taxBase;
                        }
                    } else {
                        $correctTaxAmount = $order->total_tax_amount ?? 0;
                    }
                    $correctTotal += $correctTaxAmount;
                    $correctTotal += $serviceTotal;

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

                    $serviceTotal = 0;
                    if ($order->charges && $order->charges->count() > 0) {
                        foreach ($order->charges as $chargeRelation) {
                            if ($chargeRelation->charge) {
                                $serviceTotal += $chargeRelation->charge->getAmount($discountedSubtotal);
                            }
                        }
                    }

                    $includeChargesInTaxBase = restaurant()->include_charges_in_tax_base ?? true;
                    $taxBase = $includeChargesInTaxBase ? ($discountedSubtotal + $serviceTotal) : $discountedSubtotal;

                    $finalTaxAmount = 0;
                    if ($order->tax_mode === 'order' && $order->taxes && $order->taxes->count() > 0) {
                        foreach ($order->taxes as $tax) {
                            $finalTaxAmount += ($tax->tax_percent / 100) * $taxBase;
                        }
                    } else {
                        $finalTaxAmount = $order->total_tax_amount ?? 0;
                    }
                    $finalTotal += $finalTaxAmount;
                    $finalTotal += $serviceTotal;

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
            } elseif ($existingRedeemedPoints != $this->loyaltyPointsRedeemed) {
                // Points changed - remove old redemption and add new one
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

                    $serviceTotal = 0;
                    if ($order->charges && $order->charges->count() > 0) {
                        foreach ($order->charges as $chargeRelation) {
                            if ($chargeRelation->charge) {
                                $serviceTotal += $chargeRelation->charge->getAmount($discountedSubtotal);
                            }
                        }
                    }

                    $includeChargesInTaxBase = restaurant()->include_charges_in_tax_base ?? true;
                    $taxBase = $includeChargesInTaxBase ? ($discountedSubtotal + $serviceTotal) : $discountedSubtotal;

                    $finalTaxAmount = 0;
                    if ($order->tax_mode === 'order' && $order->taxes && $order->taxes->count() > 0) {
                        foreach ($order->taxes as $tax) {
                            $finalTaxAmount += ($tax->tax_percent / 100) * $taxBase;
                        }
                    } else {
                        $finalTaxAmount = $order->total_tax_amount ?? 0;
                    }
                    $finalTotal += $finalTaxAmount;
                    $finalTotal += $serviceTotal;

                    $finalTotal += ($order->tip_amount ?? 0);
                    $finalTotal += ($order->delivery_fee ?? 0);

                    $order->update([
                        'total' => round($finalTotal, 2),
                        'total_tax_amount' => round($finalTaxAmount, 2),
                    ]);
                }
            }
        } elseif ($existingRedeemedPoints > 0 && $this->loyaltyPointsRedeemed == 0) {
            // Points were redeemed but now removed - remove redemption
            $loyaltyService = app(\Modules\Loyalty\Services\LoyaltyService::class);
            $loyaltyService->removeRedemption($order);
            $order->refresh();
        }
    }


    public function handleDraftOrderLoyaltyRedemption(\App\Models\Order $order): void
    {
        // CRITICAL: Redeem stamps and points NOW that items exist (for draft orders)
        $loyaltyRedemptionHappened = false;
        if ($order->customer_id) {
            // Don't auto-redeem just because items have stamp_rule_id set
            if ($this->isStampsEnabledForPOS()) {
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

                // Only redeem if stamps were actually applied
                if (empty($stampRuleIdsToRedeem)) {
                    // Skip stamp redemption - no stamps were applied in POS
                } else {
                    // Redeem stamps for each stamp rule ONCE
                    foreach ($stampRuleIdsToRedeem as $stampRuleIdToRedeem) {
                        if (!$stampRuleIdToRedeem) {
                            continue;
                        }

                        $stampRule = \Modules\Loyalty\Entities\LoyaltyStampRule::find($stampRuleIdToRedeem);
                        if ($stampRule && in_array($stampRule->reward_type, ['discount_percent', 'discount_amount'])) {
                            // For discount stamps, amounts are already reduced in POS.
                            // Only record redemption + persist discount totals.
                            $this->finalizeDiscountStampRedemptionForDraft($order, $stampRuleIdToRedeem);
                            continue;
                        }

                        // For free-item stamps, use service to add items and redeem.
                        $this->redeemStampsForAllEligibleItems($order, $stampRuleIdToRedeem);
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
            }

            // Redeem points if selected
            if ($this->loyaltyPointsRedeemed > 0 && $this->loyaltyDiscountAmount > 0 && $this->isPointsEnabledForPOS() && module_enabled('Loyalty')) {
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


    public function handleKotOrderLoyaltyRedemption(\App\Models\Order $order): void
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

    public function getOrderLoyaltyDiscount(\App\Models\Order $order): float
    {
        return (float)($order->loyalty_discount_amount ?? 0);
    }

    public function getOrderStampDiscount(\App\Models\Order $order): float
    {
        return (float)($order->stamp_discount_amount ?? 0);
    }

    public function appendLoyaltyFieldsToOrderUpdate(\App\Models\Order $order, array &$updateData): void
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

    public function redeemStampsForAllEligibleItems(Order $order, int $stampRuleId): void
    {
        if (!module_enabled('Loyalty')) {
            return;
        }

        // CRITICAL: Prevent duplicate calls for the same stamp rule in the same request
        $cacheKey = $order->id . '_' . $stampRuleId;
        if (isset(self::$processedStampRules[$cacheKey])) {
            return;
        }

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
                return;
            }

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
            $freeItemsCount = (int) $order->items()
                ->where('stamp_rule_id', $stampRuleId)
                ->where('is_free_item_from_stamp', true)
                ->sum('quantity');

            // Count discounted items (items with stamp_rule_id but not free)
            // CRITICAL: Also check if discount was already applied to item amount
            $eligibleItems = $order->items()
                ->where('stamp_rule_id', $stampRuleId)
                ->where(function ($q) {
                    $q->whereNull('is_free_item_from_stamp')
                        ->orWhere('is_free_item_from_stamp', false);
                })
                ->sum('quantity');

            // Total eligible items = free items + discounted items needing redemption
            $eligibleItemsCount = $freeItemsCount + (int) $eligibleItems;

            // Count existing transactions for this stamp rule and order
            $existingTransactionsCount = 0;
            if (module_enabled('Loyalty')) {
                $existingTransactionsStamps = (int) \Modules\Loyalty\Entities\LoyaltyStampTransaction::where('order_id', $order->id)
                    ->where('stamp_rule_id', $stampRuleId)
                    ->where('type', 'REDEEM')
                    ->lockForUpdate() // Lock to prevent concurrent access
                    ->sum('stamps');
                $existingTransactionsCount = (int) floor(abs($existingTransactionsStamps) / max(1, (int) $stampsRequired));
            }

            // Calculate how many items should be redeemed
            $itemsToRedeem = $eligibleItemsCount;

            // CRITICAL: Only proceed if:
            // 1. There are eligible items
            // 2. Customer has enough stamps
            // 3. Transactions don't already match eligible items (prevent duplicate)
            if ($eligibleItemsCount <= 0) {
                return;
            }

            if ($stampsBeforeRedemption < $stampsRequired) {
                return;
            }

            // CRITICAL: If transactions already exist for all eligible items, skip redemption
            // This prevents duplicate redemption
            if ($existingTransactionsCount >= $eligibleItemsCount) {
                return;
            }

            // If POS already applied the reward (discount or free item), only deduct stamps here
            $hasAppliedDiscount = false;
            $discountedItems = $order->items()
                ->where('stamp_rule_id', $stampRuleId)
                ->where(function ($q) {
                    $q->whereNull('is_free_item_from_stamp')
                        ->orWhere('is_free_item_from_stamp', false);
                })
                ->get();

            foreach ($discountedItems as $item) {
                $expected = (float)($item->price ?? 0) * (int)($item->quantity ?? 1);
                $actual = (float)($item->amount ?? 0);
                if ($expected > $actual + 0.01) {
                    $hasAppliedDiscount = true;
                    break;
                }
            }

            if ($freeItemsCount > 0 || $hasAppliedDiscount) {
                $itemsToRedeem = max(0, $eligibleItemsCount - $existingTransactionsCount);
                if ($itemsToRedeem <= 0) {
                    return;
                }

                $maxByStamps = (int) floor($stampsBeforeRedemption / max(1, (int) $stampsRequired));
                if ($maxByStamps <= 0) {
                    return;
                }
                $itemsToRedeem = min($itemsToRedeem, $maxByStamps);

                $totalStampsNeeded = $itemsToRedeem * $stampsRequired;

                $customerStamp->stamps_redeemed += $totalStampsNeeded;
                $customerStamp->last_redeemed_at = now();
                $customerStamp->save();

                for ($i = 0; $i < $itemsToRedeem; $i++) {
                    \Modules\Loyalty\Entities\LoyaltyStampTransaction::create([
                        'restaurant_id' => $restaurantId,
                        'customer_id' => $customerId,
                        'stamp_rule_id' => $stampRuleId,
                        'order_id' => $order->id,
                        'type' => 'REDEEM',
                        'stamps' => -$stampsRequired,
                        'reason' => __('loyalty::app.stampsRedeemedForOrder', [
                            'order_number' => $order->order_number,
                        ]),
                    ]);
                }

                // Persist stamp discount amount for reporting if missing
                if ($freeItemsCount <= 0) {
                    $totalDiscountApplied = 0;
                    $discountedItems = $order->items()
                        ->where('stamp_rule_id', $stampRuleId)
                        ->where(function ($q) {
                            $q->whereNull('is_free_item_from_stamp')
                                ->orWhere('is_free_item_from_stamp', false);
                        })
                        ->get();
                    foreach ($discountedItems as $item) {
                        $expected = (float)($item->price ?? 0) * (int)($item->quantity ?? 1);
                        $actual = (float)($item->amount ?? 0);
                        $totalDiscountApplied += max(0, round($expected - $actual, 2));
                    }
                    if ($totalDiscountApplied > 0) {
                        $order->update([
                            'stamp_discount_amount' => max((float)($order->stamp_discount_amount ?? 0), $totalDiscountApplied),
                        ]);
                    }
                }

                $order->refresh();
                self::$processedStampRules[$cacheKey] = true;
                return;
            }

            // CRITICAL: Call the service ONCE per stamp rule
            // The service now handles redeeming all eligible items in one call
            $loyaltyService = app(\Modules\Loyalty\Services\LoyaltyService::class);
            $result = $loyaltyService->redeemStamps($order, $stampRuleId);

            if (!is_array($result) || !($result['success'] ?? false)) {
                return;
            }

            // Refresh to get updated customer stamp balance
            $customerStamp->refresh();
            $stampsAfterRedemption = $customerStamp->stamps_earned - $customerStamp->stamps_redeemed;

            // Refresh order to get updated items
            $order->refresh();
            $order->load('items');

            // Mark as processed to prevent duplicate calls
            self::$processedStampRules[$cacheKey] = true;
        });
    }

    public function finalizeDiscountStampRedemptionForDraft(Order $order, int $stampRuleId): void
    {
        if (!module_enabled('Loyalty')) {
            return;
        }

        $stampRule = \Modules\Loyalty\Entities\LoyaltyStampRule::find($stampRuleId);
        if (!$stampRule) {
            return;
        }

        $order->load('items', 'branch');

        $eligibleItems = $order->items()
            ->where('stamp_rule_id', $stampRuleId)
            ->where(function ($q) {
                $q->whereNull('is_free_item_from_stamp')
                    ->orWhere('is_free_item_from_stamp', false);
            })
            ->get();

        if ($eligibleItems->isEmpty()) {
            return;
        }

        // Compute total discount already applied to items
        $totalDiscountApplied = 0;
        foreach ($eligibleItems as $item) {
            $expected = (float)($item->price ?? 0) * (int)($item->quantity ?? 1);
            $actual = (float)($item->amount ?? 0);
            $itemDiscount = max(0, round($expected - $actual, 2));
            $totalDiscountApplied += $itemDiscount;
        }

        // Persist stamp discount amount on order (avoid double-adding)
        if ($totalDiscountApplied > 0) {
            $order->update([
                'stamp_discount_amount' => max((float)($order->stamp_discount_amount ?? 0), $totalDiscountApplied),
            ]);
        }

        // Redeem stamps from customer account if not already redeemed
        $restaurantId = $order->branch->restaurant_id ?? null;
        $customerId = $order->customer_id;
        if (!$restaurantId || !$customerId) {
            return;
        }

        $stampsRequired = (int)($stampRule->stamps_required ?? 1);
        $itemsToRedeem = (int)$eligibleItems->sum('quantity');
        if ($itemsToRedeem <= 0 || $stampsRequired <= 0) {
            return;
        }

        $existingTransactionsStamps = (int)\Modules\Loyalty\Entities\LoyaltyStampTransaction::where('order_id', $order->id)
            ->where('stamp_rule_id', $stampRuleId)
            ->where('type', 'REDEEM')
            ->sum('stamps');
        $existingTransactionsCount = (int) floor(abs($existingTransactionsStamps) / $stampsRequired);

        $itemsToRedeem = max(0, $itemsToRedeem - $existingTransactionsCount);
        if ($itemsToRedeem <= 0) {
            return;
        }

        $customerStamp = \Modules\Loyalty\Entities\CustomerStamp::getOrCreate($restaurantId, $customerId, $stampRuleId);
        $availableStamps = $customerStamp->getAvailableStampsAttribute();
        $maxItemsByStamps = intdiv($availableStamps, $stampsRequired);
        $itemsToRedeem = min($itemsToRedeem, $maxItemsByStamps);
        if ($itemsToRedeem <= 0) {
            return;
        }

        $stampsToRedeem = $itemsToRedeem * $stampsRequired;
        $customerStamp->stamps_redeemed += $stampsToRedeem;
        $customerStamp->last_redeemed_at = now();
        $customerStamp->save();

        for ($i = 0; $i < $itemsToRedeem; $i++) {
            \Modules\Loyalty\Entities\LoyaltyStampTransaction::create([
                'restaurant_id' => $restaurantId,
                'customer_id' => $customerId,
                'stamp_rule_id' => $stampRuleId,
                'order_id' => $order->id,
                'type' => 'REDEEM',
                'stamps' => -$stampsRequired,
                'reason' => __('loyalty::app.stampsRedeemedForOrder', [
                    'order_number' => $order->order_number,
                ]),
            ]);
        }
    }
}
