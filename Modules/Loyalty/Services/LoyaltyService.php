<?php

namespace Modules\Loyalty\Services;

use App\Models\Order;
use App\Models\Customer;
use Modules\Loyalty\Entities\LoyaltyAccount;
use Modules\Loyalty\Entities\LoyaltyLedger;
use Modules\Loyalty\Entities\LoyaltySetting;
use Modules\Loyalty\Entities\LoyaltyTier;
use Modules\Loyalty\Entities\LoyaltyStampRule;
use Modules\Loyalty\Entities\CustomerStamp;
use Modules\Loyalty\Entities\LoyaltyStampTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LoyaltyService
{
    /**
     * Check if Loyalty module is in restaurant's package.
     */
    protected function isModuleInPackage($restaurantId): bool
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
     * Get or create loyalty account for a customer.
     */
    public function getOrCreateAccount($restaurantId, $customerId): LoyaltyAccount
    {
        return LoyaltyAccount::firstOrCreate(
            [
                'restaurant_id' => $restaurantId,
                'customer_id' => $customerId,
            ],
            [
                'points_balance' => 0,
            ]
        );
    }

    /**
     * Get customer's available points.
     */
    public function getAvailablePoints($restaurantId, $customerId): int
    {
        // Check if module is in package
        if (!$this->isModuleInPackage($restaurantId)) {
            return 0;
        }

        $account = LoyaltyAccount::where('restaurant_id', $restaurantId)
            ->where('customer_id', $customerId)
            ->first();

        return $account ? $account->points_balance : 0;
    }

    /**
     * Calculate points that can be earned for an order.
     */
    public function calculateEarnablePoints(Order $order): int
    {
        if (!$order->customer_id) {
            return 0;
        }

        $restaurantId = $order->branch->restaurant_id;

        // Check if module is in package
        if (!$this->isModuleInPackage($restaurantId)) {
            return 0;
        }

        $settings = LoyaltySetting::getForRestaurant($restaurantId);

        if (!$settings->isEnabled()) {
            return 0;
        }

        // If points were redeemed, no points earned
        if ($order->loyalty_points_redeemed > 0) {
            return 0;
        }

        $basePoints = $settings->calculatePointsEarned($order->sub_total ?? 0);
        
        // Apply tier multiplier if customer has a tier
        $account = LoyaltyAccount::where('restaurant_id', $restaurantId)
            ->where('customer_id', $order->customer_id)
            ->first();
        
        if ($account && $account->tier_id) {
            $tier = LoyaltyTier::find($account->tier_id);
            if ($tier && $tier->earning_multiplier > 0) {
                $basePoints = (int) floor($basePoints * $tier->earning_multiplier);
            }
        }
        
        return $basePoints;
    }

    /**
     * Earn points for a completed order.
     */
    public function earnPoints(Order $order): bool
    {
        if (!$order->customer_id) {
            return false;
        }

        $restaurantId = $order->branch->restaurant_id;

        // Check if module is in package
        if (!$this->isModuleInPackage($restaurantId)) {
            return false;
        }

        $settings = LoyaltySetting::getForRestaurant($restaurantId);

        if (!$settings->isEnabled()) {
            return false;
        }

        // If points were redeemed, no points earned
        if ($order->loyalty_points_redeemed > 0) {
            return false;
        }

        // If stamps were redeemed (discount applied or free item added), no points earned
        if ($order->stamp_discount_amount > 0) {
            return false;
        }

        // Check if any free items from stamp redemption exist in order
        $hasFreeItemFromStamp = $order->items()
            ->where('is_free_item_from_stamp', true)
            ->exists();
        
        if ($hasFreeItemFromStamp) {
            return false;
        }

        $points = $this->calculateEarnablePoints($order);

        if ($points <= 0) {
            return false;
        }

        try {
            DB::beginTransaction();

            // Get or create account
            $account = $this->getOrCreateAccount($restaurantId, $order->customer_id);

            // Create ledger entry
            LoyaltyLedger::create([
                'restaurant_id' => $restaurantId,
                'customer_id' => $order->customer_id,
                'order_id' => $order->id,
                'type' => 'EARN',
                'points' => $points,
                'reason' => __('loyalty::app.pointsEarnedForOrder', ['order_number' => $order->order_number]),
            ]);

            // Update account balance
            $account->updateBalance();
            
            // Update tier based on new balance
            $account->updateTier();

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Loyalty: Failed to earn points', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Calculate maximum discount available for redemption.
     */
    public function calculateMaxDiscount($restaurantId, $customerId, $orderSubtotal): array
    {
        // Check if module is in package
        if (!$this->isModuleInPackage($restaurantId)) {
            return [
                'available_points' => 0,
                'max_discount' => 0,
                'points_required' => 0,
            ];
        }

        $settings = LoyaltySetting::getForRestaurant($restaurantId);

        if (!$settings->isEnabled()) {
            return [
                'available_points' => 0,
                'max_discount' => 0,
                'points_required' => 0,
            ];
        }

        $availablePoints = $this->getAvailablePoints($restaurantId, $customerId);

        if ($availablePoints < $settings->min_redeem_points) {
            return [
                'available_points' => $availablePoints,
                'max_discount' => 0,
                'points_required' => 0,
            ];
        }

        $maxDiscount = $orderSubtotal * ($settings->max_discount_percent / 100);
        $discountFromPoints = $availablePoints * $settings->value_per_point;
        $actualDiscount = min($discountFromPoints, $maxDiscount);
        $pointsRequired = $settings->calculatePointsForDiscount($actualDiscount);

        return [
            'available_points' => $availablePoints,
            'max_discount' => round($actualDiscount, 2),
            'points_required' => $pointsRequired,
        ];
    }

    /**
     * Redeem points for an order.
     */
    public function redeemPoints(Order $order, int $pointsToRedeem): array
    {
        if (!$order->customer_id) {
            return [
                'success' => false,
                'message' => __('loyalty::app.orderMustHaveCustomer'),
            ];
        }

        $restaurantId = $order->branch->restaurant_id;

        // Check if module is in package
        if (!$this->isModuleInPackage($restaurantId)) {
            return [
                'success' => false,
                'message' => __('loyalty::app.loyaltyModuleNotInPackage'),
            ];
        }

        $settings = LoyaltySetting::getForRestaurant($restaurantId);

        if (!$settings->isEnabled()) {
            return [
                'success' => false,
                'message' => __('loyalty::app.loyaltyProgramNotEnabled'),
            ];
        }

        $availablePoints = $this->getAvailablePoints($restaurantId, $order->customer_id);

        if ($pointsToRedeem < $settings->min_redeem_points) {
            return [
                'success' => false,
                'message' => __('loyalty::app.minPointsRequired', ['min_points' => $settings->min_redeem_points]),
            ];
        }

        if ($pointsToRedeem > $availablePoints) {
            return [
                'success' => false,
                'message' => __('loyalty::app.insufficientPoints'),
            ];
        }

        $existingRedeemedPoints = (int) ($order->loyalty_points_redeemed ?? 0);
        $existingRedeemedDiscount = (float) ($order->loyalty_discount_amount ?? 0);

        // Calculate discount with tier multiplier
        $subtotal = $order->sub_total ?? 0;
        $maxDiscount = $subtotal * ($settings->max_discount_percent / 100);
        $remainingDiscountCap = max(0, (float) $maxDiscount - $existingRedeemedDiscount);
        $baseDiscountFromPoints = $pointsToRedeem * $settings->value_per_point;
        
        // Apply tier redemption multiplier if customer has a tier
        $account = $this->getOrCreateAccount($restaurantId, $order->customer_id);
        $tierMultiplier = 1.00;
        if ($account->tier_id) {
            $tier = LoyaltyTier::find($account->tier_id);
            if ($tier && $tier->redemption_multiplier > 0) {
                $tierMultiplier = $tier->redemption_multiplier;
            }
        }
        
        $discountFromPoints = $baseDiscountFromPoints * $tierMultiplier;
        $actualDiscount = min($discountFromPoints, $remainingDiscountCap);

        // Recalculate points needed based on actual discount
        $actualPointsNeeded = $settings->calculatePointsForDiscount($actualDiscount);

        if ($actualPointsNeeded > $availablePoints) {
            $actualPointsNeeded = $availablePoints;
            $actualDiscount = $actualPointsNeeded * $settings->value_per_point;
            $actualDiscount = min($actualDiscount, $remainingDiscountCap);
        }
        
        // Don't proceed if no points can be redeemed (subtotal is 0 or too low)
        if ($actualPointsNeeded <= 0 || $actualDiscount <= 0) {
            return [
                'success' => false,
                'message' => __('loyalty::app.noPointsCanBeRedeemed'),
            ];
        }

        if ($remainingDiscountCap <= 0) {
            return [
                'success' => false,
                'message' => __('loyalty::app.noPointsCanBeRedeemed'),
            ];
        }

        try {
            DB::beginTransaction();

            // Lock account to prevent concurrent modifications
            $account = $this->getOrCreateAccount($restaurantId, $order->customer_id);
            $account = LoyaltyAccount::lockForUpdate()
                ->where('id', $account->id)
                ->first();
            
            // Create ledger entry ONLY if points are actually being redeemed
            LoyaltyLedger::create([
                'restaurant_id' => $restaurantId,
                'customer_id' => $order->customer_id,
                'order_id' => $order->id,
                'type' => 'REDEEM',
                'points' => -$actualPointsNeeded,
                'reason' => __('loyalty::app.pointsRedeemedForOrder', ['order_number' => $order->order_number]),
            ]);

            // Update account balance
            $account->updateBalance();
            
            // Update tier based on new balance
            $account->updateTier();

            // Update order - preserve stamp_discount_amount if it exists
            Log::info('REDEEM POINTS - BEFORE SAVE', [
                'order_id' => $order->id,
                'loyalty_points_redeemed' => $actualPointsNeeded,
                'loyalty_discount_amount' => $actualDiscount,
                'existing_loyalty_points_redeemed' => $existingRedeemedPoints,
                'existing_loyalty_discount_amount' => $existingRedeemedDiscount,
                'existing_stamp_discount' => $order->stamp_discount_amount,
                'sub_total' => $order->sub_total,
            ]);
            
            $order->loyalty_points_redeemed = $existingRedeemedPoints + $actualPointsNeeded;
            $order->loyalty_discount_amount = round($existingRedeemedDiscount + $actualDiscount, 2);
            
            // CRITICAL: Preserve stamp_discount_amount if stamps were also redeemed
            // Without this, save() will overwrite with null/0
            if ($order->stamp_discount_amount > 0) {
                // Don't modify it - it's already set
            }
            
            $order->save();
            
            Log::info('REDEEM POINTS - AFTER SAVE', [
                'order_id' => $order->id,
                'loyalty_points_redeemed' => $order->loyalty_points_redeemed,
                'loyalty_discount_amount' => $order->loyalty_discount_amount,
                'stamp_discount_amount' => $order->stamp_discount_amount,
                'sub_total' => $order->sub_total,
                'total' => $order->total,
            ]);

            DB::commit();

            return [
                'success' => true,
                'points_redeemed' => $actualPointsNeeded,
                'discount_amount' => $actualDiscount,
                'message' => __('loyalty::app.pointsRedeemedSuccessfully'),
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Loyalty: Failed to redeem points', [
                'order_id' => $order->id,
                'points' => $pointsToRedeem,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => __('loyalty::app.failedToRedeemPoints', ['error' => $e->getMessage()]),
            ];
        }
    }

    /**
     * Remove redemption from an order (if order is cancelled or payment fails).
     */
    public function removeRedemption(Order $order): bool
    {
        if (!$order->loyalty_points_redeemed || $order->loyalty_points_redeemed <= 0) {
            return false;
        }

        if (!$order->customer_id) {
            return false;
        }

        $restaurantId = $order->branch->restaurant_id;

        try {
            DB::beginTransaction();

            // Find and delete ALL REDEEM ledger entries for this order (to handle duplicates)
            $deletedCount = LoyaltyLedger::where('restaurant_id', $restaurantId)
                ->where('customer_id', $order->customer_id)
                ->where('order_id', $order->id)
                ->where('type', 'REDEEM')
                ->delete();

            if ($deletedCount > 0) {
                // Update account balance after deleting entries
                $account = $this->getOrCreateAccount($restaurantId, $order->customer_id);
                $account->updateBalance();
            }

            // Clear order redemption
            $order->loyalty_points_redeemed = 0;
            $order->loyalty_discount_amount = 0;
            $order->save();

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Loyalty: Failed to remove redemption', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Earn stamps for order items.
     */
    public function earnStamps(Order $order): bool
    {
        if (!$order->customer_id) {
            return false;
        }

        $restaurantId = $order->branch->restaurant_id;

        // Check if module is in package
        if (!$this->isModuleInPackage($restaurantId)) {
            return false;
        }

        $settings = LoyaltySetting::getForRestaurant($restaurantId);

        if (!$settings->isEnabled()) {
            return false;
        }

        // If stamps were redeemed (discount applied or free item added), no stamps earned
        // Only block earning if stamps were actually redeemed (not just if field exists)
        $hasStampRedemption = $order->stamp_discount_amount > 0;
        
        // Check if any free items from stamp redemption exist in order
        $hasFreeItemFromStamp = $order->items()
            ->where('is_free_item_from_stamp', true)
            ->exists();
        
        // Only block earning if stamps were actually redeemed
        if ($hasStampRedemption || $hasFreeItemFromStamp) {
            return false;
        }

        try {
            DB::beginTransaction();

            $stampsEarned = false;

            // Get all stamp rules for this restaurant
            $stampRules = LoyaltyStampRule::getActiveRulesForRestaurant($restaurantId);

            // Group order items by menu_item_id and count quantities
            // Exclude free items from stamp redemption
            $orderItems = $order->items()
                ->where('is_free_item_from_stamp', false)
                ->get();
            $itemCounts = [];
            foreach ($orderItems as $item) {
                $menuItemId = $item->menu_item_id;
                if (!isset($itemCounts[$menuItemId])) {
                    $itemCounts[$menuItemId] = 0;
                }
                $itemCounts[$menuItemId] += $item->quantity;
            }

            // Process each stamp rule
            foreach ($stampRules as $rule) {
                $menuItemId = $rule->menu_item_id;
                if (isset($itemCounts[$menuItemId]) && $itemCounts[$menuItemId] > 0) {
                    // Customer earns 1 stamp per item ordered
                    $stampsToEarn = $itemCounts[$menuItemId];

                    // Get or create customer stamp record
                    $customerStamp = CustomerStamp::getOrCreate(
                        $restaurantId,
                        $order->customer_id,
                        $rule->id
                    );

                    // Update stamps earned
                    $customerStamp->stamps_earned += $stampsToEarn;
                    $customerStamp->last_earned_at = now();
                    $customerStamp->save();

                    // Create transaction record
                    LoyaltyStampTransaction::create([
                        'restaurant_id' => $restaurantId,
                        'customer_id' => $order->customer_id,
                        'stamp_rule_id' => $rule->id,
                        'order_id' => $order->id,
                        'type' => 'EARN',
                        'stamps' => $stampsToEarn,
                        'reason' => __('loyalty::app.stampsEarnedForOrder', [
                            'order_number' => $order->order_number,
                            'item_name' => $rule->menuItem->item_name ?? 'Item',
                        ]),
                    ]);

                    $stampsEarned = true;
                }
            }

            DB::commit();
            return $stampsEarned;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Loyalty: Failed to earn stamps', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Get customer's available stamps for a stamp rule.
     */
    public function getAvailableStamps($restaurantId, $customerId, $stampRuleId): int
    {
        $customerStamp = CustomerStamp::where('restaurant_id', $restaurantId)
            ->where('customer_id', $customerId)
            ->where('stamp_rule_id', $stampRuleId)
            ->first();

        return $customerStamp ? $customerStamp->getAvailableStampsAttribute() : 0;
    }

    /**
     * Get all customer stamps with rules.
     */
    public function getCustomerStamps($restaurantId, $customerId)
    {
        return CustomerStamp::where('restaurant_id', $restaurantId)
            ->where('customer_id', $customerId)
            ->with(['stampRule.menuItem', 'stampRule.rewardMenuItem'])
            ->get()
            ->map(function ($customerStamp) {
                return [
                    'rule' => $customerStamp->stampRule,
                    'stamps_earned' => $customerStamp->stamps_earned,
                    'stamps_redeemed' => $customerStamp->stamps_redeemed,
                    'available_stamps' => $customerStamp->getAvailableStampsAttribute(),
                    'can_redeem' => $customerStamp->canRedeem(),
                    'stamps_required' => $customerStamp->stampRule->stamps_required,
                ];
            });
    }

    /**
     * Redeem stamps for an order.
     */
    public function redeemStamps(Order $order, int $stampRuleId): array
    {
        if (!$order->customer_id) {
            return [
                'success' => false,
                'message' => __('loyalty::app.orderMustHaveCustomer'),
            ];
        }

        $restaurantId = $order->branch->restaurant_id;

        // Check if module is in package
        if (!$this->isModuleInPackage($restaurantId)) {
            return [
                'success' => false,
                'message' => __('loyalty::app.loyaltyModuleNotInPackage'),
            ];
        }

        $settings = LoyaltySetting::getForRestaurant($restaurantId);

        if (!$settings->isEnabled()) {
            return [
                'success' => false,
                'message' => __('loyalty::app.loyaltyProgramNotEnabled'),
            ];
        }

        $stampRule = LoyaltyStampRule::find($stampRuleId);

        if (!$stampRule || $stampRule->restaurant_id != $restaurantId || !$stampRule->is_active) {
            return [
                'success' => false,
                'message' => __('loyalty::app.stampRuleNotFound'),
            ];
        }

        // Count how many items are eligible for redemption
        // On customer site, items don't have stamp_rule_id set yet, so check by menu_item_id
        $order->load('items');
        $eligibleItemsCount = (int) $order->items()
            ->where('menu_item_id', $stampRule->menu_item_id)
            ->where(function ($q) {
                $q->whereNull('is_free_item_from_stamp')
                  ->orWhere('is_free_item_from_stamp', false);
            })
            ->sum('quantity');

        
        if ($eligibleItemsCount <= 0) {
            return [
                'success' => false,
                'message' => __('loyalty::app.noEligibleItemsForStampRedemption'),
            ];
        }
        
        // Count existing transactions for this stamp rule and order
        $existingTransactions = LoyaltyStampTransaction::where('restaurant_id', $restaurantId)
            ->where('customer_id', $order->customer_id)
            ->where('order_id', $order->id)
            ->where('stamp_rule_id', $stampRuleId)
            ->where('type', 'REDEEM')
            ->get();
        
        $existingTransactionsCount = (int) floor(abs($existingTransactions->sum('stamps')) / max(1, (int) $stampRule->stamps_required));
        
        // Calculate how many items still need redemption
        $itemsToRedeem = max(0, $eligibleItemsCount - $existingTransactionsCount);
        
        if ($itemsToRedeem <= 0) {
            // All items already redeemed
            Log::info('LoyaltyService::redeemStamps - All items already redeemed', [
                'order_id' => $order->id,
                'stamp_rule_id' => $stampRuleId,
                'eligible_items' => $eligibleItemsCount,
                'existing_transactions' => $existingTransactionsCount,
            ]);
            
            return [
                'success' => false,
                'message' => __('loyalty::app.stampsAlreadyRedeemed'),
            ];
        }

        // Get customer stamp and check availability BEFORE transaction
        $customerStamp = CustomerStamp::getOrCreate($restaurantId, $order->customer_id, $stampRuleId);
        
        // Calculate total stamps needed for all items to redeem
        $totalStampsNeeded = $itemsToRedeem * $stampRule->stamps_required;
        $availableStamps = $customerStamp->getAvailableStampsAttribute();

        if ($availableStamps < $stampRule->stamps_required) {
            return [
                'success' => false,
                'message' => __('loyalty::app.insufficientStamps', [
                    'required' => $stampRule->stamps_required,
                    'available' => $availableStamps,
                ]),
            ];
        }
        
        // Check if customer has enough stamps for all items
        if ($availableStamps < $totalStampsNeeded) {
            // Redeem only what customer can afford
            $itemsToRedeem = floor($availableStamps / $stampRule->stamps_required);
            $totalStampsNeeded = $itemsToRedeem * $stampRule->stamps_required;
        }

        try {
            DB::beginTransaction();

            // Lock customer stamp to prevent concurrent modifications
            $customerStamp = CustomerStamp::lockForUpdate()
                ->where('id', $customerStamp->id)
                ->first();
            
            // Re-check available stamps after locking
            if (!$customerStamp || !$customerStamp->canRedeem()) {
                DB::rollBack();
                return [
                    'success' => false,
                    'message' => __('loyalty::app.insufficientStamps', [
                        'required' => $stampRule->stamps_required,
                        'available' => $customerStamp ? $customerStamp->getAvailableStampsAttribute() : 0,
                    ]),
                ];
            }
            
            // Re-check eligible items count and existing transactions after locking
            $order->refresh();
            $order->load('items');
            // Check by menu_item_id since items may not have stamp_rule_id set yet
            $eligibleItemsCountAfterLock = (int) $order->items()
                ->where('menu_item_id', $stampRule->menu_item_id)
                ->where(function ($q) {
                    $q->whereNull('is_free_item_from_stamp')
                      ->orWhere('is_free_item_from_stamp', false);
                })
                ->sum('quantity');
            
            $existingTransactionsCheck = LoyaltyStampTransaction::lockForUpdate()
                ->where('restaurant_id', $restaurantId)
                ->where('customer_id', $order->customer_id)
                ->where('order_id', $order->id)
                ->where('stamp_rule_id', $stampRuleId)
                ->where('type', 'REDEEM')
                ->get();
            
            $existingTransactionsCountAfterLock = (int) floor(abs($existingTransactionsCheck->sum('stamps')) / max(1, (int) $stampRule->stamps_required));
            $itemsToRedeemAfterLock = max(0, $eligibleItemsCountAfterLock - $existingTransactionsCountAfterLock);
            
            if ($itemsToRedeemAfterLock <= 0) {
                DB::rollBack();
                return [
                    'success' => false,
                    'message' => __('loyalty::app.stampsAlreadyRedeemed'),
                ];
            }
            
            // Update itemsToRedeem and totalStampsNeeded based on locked values
            $itemsToRedeem = min($itemsToRedeem, $itemsToRedeemAfterLock);
            $totalStampsNeeded = $itemsToRedeem * $stampRule->stamps_required;
            
            // Re-check if customer has enough stamps after locking
            $availableStampsAfterLock = $customerStamp->getAvailableStampsAttribute();
            if ($availableStampsAfterLock < $stampRule->stamps_required) {
                DB::rollBack();
                return [
                    'success' => false,
                    'message' => __('loyalty::app.insufficientStamps', [
                        'required' => $stampRule->stamps_required,
                        'available' => $availableStampsAfterLock,
                    ]),
                ];
            }
            
            // Adjust if customer doesn't have enough for all items
            if ($availableStampsAfterLock < $totalStampsNeeded) {
                $itemsToRedeem = floor($availableStampsAfterLock / $stampRule->stamps_required);
                $totalStampsNeeded = $itemsToRedeem * $stampRule->stamps_required;
            }

            // Calculate reward value
            $baseRewardValue = $stampRule->calculateRewardValue($order->sub_total ?? 0);
            
            // Apply tier redemption multiplier for discount rewards (not for free items)
            $tierMultiplier = 1.00;
            if (in_array($stampRule->reward_type, ['discount_percent', 'discount_amount'])) {
                $account = $this->getOrCreateAccount($restaurantId, $order->customer_id);
                if ($account && $account->tier_id) {
                    $tier = LoyaltyTier::find($account->tier_id);
                    if ($tier && $tier->redemption_multiplier > 0) {
                        $tierMultiplier = $tier->redemption_multiplier;
                    }
                }
            }
            
            $rewardValue = $baseRewardValue * $tierMultiplier;

            // Track if reward was successfully applied (for stamp deduction)
            $rewardApplied = false;
            
            // Apply reward based on reward type
            switch ($stampRule->reward_type) {
                case 'free_item':
                    // Add free item to order
                    // Allow multiple free items if customer has enough stamps
                    if ($stampRule->reward_menu_item_id) {
                        $rewardMenuItem = $stampRule->rewardMenuItem;
                        if ($rewardMenuItem) {
                            // Get item price (use variation price if exists, otherwise base price)
                            $itemPrice = $rewardMenuItem->price ?? 0;
                            $variationId = null;
                            
                            // If variation is specified, use variation price
                            if ($stampRule->reward_menu_item_variation_id) {
                                $variation = $stampRule->rewardMenuItemVariation;
                                if ($variation) {
                                    $variationId = $variation->id;
                                    $itemPrice = $variation->price ?? $itemPrice;
                                }
                            }
                            
                            // Check if free item already exists in order (dedupe if multiple)
                            $freeItems = $order->items()
                                ->where('menu_item_id', $rewardMenuItem->id)
                                ->where('is_free_item_from_stamp', true)
                                ->where('stamp_rule_id', $stampRuleId)
                                ->when($variationId, function($query) use ($variationId) {
                                    return $query->where('menu_item_variation_id', $variationId);
                                })
                                ->get();

                            $freeItem = $freeItems->first();
                            if ($freeItems->count() > 1) {
                                $freeItems->slice(1)->each->delete();
                            }

                            $alreadyRedeemedItems = (int)($existingTransactionsCountAfterLock ?? $existingTransactionsCount ?? 0);
                            $targetQty = max(1, $alreadyRedeemedItems + (int) $itemsToRedeem);

                            if ($freeItem) {
                                $freeItem->update([
                                    'quantity' => $targetQty,
                                    'price' => $itemPrice,
                                    'amount' => 0,
                                ]);
                            } else {
                                // Add free item to order (amount = 0, but keep price for display)
                                $order->items()->create([
                                    'menu_item_id' => $rewardMenuItem->id,
                                    'menu_item_variation_id' => $variationId,
                                    'quantity' => $targetQty,
                                    'price' => $itemPrice, // Original price for display
                                    'amount' => 0, // Free item - no charge
                                    'is_free_item_from_stamp' => true,
                                    'stamp_rule_id' => $stampRuleId,
                                ]);
                            }
                            
                            // For free items, DON'T change the subtotal
                            // The free item has amount=0, so it doesn't affect the subtotal
                            // The subtotal should remain as the sum of all paid items
                            $order->refresh();
                            $order->load('items');
                            
                            // Subtotal should be sum of all item amounts (free items have amount=0)
                            $newSubTotal = $order->items->sum('amount');
                            
                            // Only update if subtotal actually changed (it shouldn't for free items)
                            // But we still need to preserve loyalty values
                            $updateData = [];
                            if ($newSubTotal != $order->sub_total) {
                                $updateData['sub_total'] = $newSubTotal;
                            }
                            
                            // CRITICAL: Preserve loyalty values
                            if ($order->loyalty_points_redeemed > 0) {
                                $updateData['loyalty_points_redeemed'] = $order->loyalty_points_redeemed;
                            }
                            if ($order->loyalty_discount_amount > 0) {
                                $updateData['loyalty_discount_amount'] = $order->loyalty_discount_amount;
                            }
                            
                            // Only update if there's something to update
                            if (!empty($updateData)) {
                                $order->update($updateData);
                            }
                            
                            $rewardApplied = true;
                        }
                    }
                    break;
                    
                case 'discount_percent':
                case 'discount_amount':
                    // Apply discount to specific order items that match the stamp rule's menu_item_id
                    $order->load('items');
                    
                    // Find order items that match this stamp rule's menu_item_id
                    $eligibleOrderItems = $order->items()
                        ->where('menu_item_id', $stampRule->menu_item_id)
                        ->where(function ($q) {
                            $q->whereNull('is_free_item_from_stamp')
                              ->orWhere('is_free_item_from_stamp', false);
                        })
                        ->get();
                    
                    if ($eligibleOrderItems->isEmpty()) {
                        // No eligible items found, cannot apply discount
                        DB::rollBack();
                        return [
                            'success' => false,
                            'message' => __('loyalty::app.noEligibleItemsForStampRedemption'),
                        ];
                    }
                    
                    // Apply discount per eligible unit (not across the whole order)
                    $totalAppliedDiscount = 0;
                    $remainingUnits = (int) $itemsToRedeem;

                    foreach ($eligibleOrderItems as $item) {
                        if ($remainingUnits <= 0) {
                            break;
                        }

                        $quantity = (int)($item->quantity ?? 1);
                        if ($quantity <= 0) {
                            continue;
                        }

                        $unitsToApply = min($quantity, $remainingUnits);

                        $originalAmount = (float)($item->amount ?? 0) + (float)($item->discount_amount ?? 0);
                        $unitOriginal = $quantity > 0 ? ($originalAmount / $quantity) : 0;

                        $unitDiscount = 0;
                        if ($stampRule->reward_type === 'discount_percent') {
                            $unitDiscount = ($unitOriginal * $stampRule->reward_value) / 100;
                        } else {
                            $unitDiscount = min($stampRule->reward_value, $unitOriginal);
                        }

                        $unitDiscount *= $tierMultiplier;
                        $itemDiscount = round($unitDiscount * $unitsToApply, 2);

                        if ($itemDiscount > 0) {
                            $newAmount = max(0, round($item->amount - $itemDiscount, 2));
                            $item->update([
                                'amount' => $newAmount,
                                'stamp_rule_id' => $stampRuleId,
                            ]);

                            $totalAppliedDiscount += $itemDiscount;
                            $remainingUnits -= $unitsToApply;
                            
                            // Update corresponding kot_items if they exist
                            // First, try to find kot_items linked to this order_item
                            $kotItems = \App\Models\KotItem::where('order_item_id', $item->id)->get();
                            
                            // If no linked kot_items found, try to find kot_items directly from order's KOTs
                            // This is needed for KOT orders where kot_items exist but aren't linked to order_items yet
                            if ($kotItems->isEmpty() && $order->kot && $order->kot->count() > 0) {
                                // Load KOTs if not loaded
                                if (!$order->relationLoaded('kot')) {
                                    $order->load('kot.items');
                                }
                                
                                // Find kot_items that match this order_item's menu_item_id and variation
                                foreach ($order->kot as $kot) {
                                    if (!$kot->relationLoaded('items')) {
                                        $kot->load('items');
                                    }
                                    
                                    $matchingKotItems = $kot->items->filter(function($kotItem) use ($item) {
                                        return $kotItem->menu_item_id == $item->menu_item_id
                                            && $kotItem->menu_item_variation_id == $item->menu_item_variation_id
                                            && ($kotItem->status ?? null) !== 'cancelled';
                                    });
                                    
                                    if ($matchingKotItems->isNotEmpty()) {
                                        $kotItems = $kotItems->merge($matchingKotItems);
                                    }
                                }
                            }
                            
                            if ($kotItems->count() > 0) {
                                // Get original item amount before this discount for proportional calculation
                                $originalItemAmount = $originalAmount;
                                
                                // Calculate total original kot_item amounts for this order item
                                $kotItemOriginalAmounts = [];
                                foreach ($kotItems as $kotItem) {
                                    $kotItemOriginalAmounts[$kotItem->id] = $kotItem->amount + ($kotItem->discount_amount ?? 0);
                                }
                                $totalKotItemOriginalAmount = array_sum($kotItemOriginalAmounts);
                                
                                foreach ($kotItems as $kotItem) {
                                    // Calculate discount for kot_item proportionally based on original amounts
                                    if ($totalKotItemOriginalAmount > 0 && $originalItemAmount > 0) {
                                        $kotItemOriginalAmount = $kotItemOriginalAmounts[$kotItem->id];
                                        // Distribute item discount proportionally to kot_items
                                        $kotItemDiscount = ($kotItemOriginalAmount / $totalKotItemOriginalAmount) * $itemDiscount;
                                    } else {
                                        $kotItemDiscount = 0;
                                    }
                                    
                                    if ($kotItemDiscount > 0) {
                                        $kotItemNewAmount = max(0, round($kotItem->amount - $kotItemDiscount, 2));
                                        $kotItem->update([
                                            'amount' => $kotItemNewAmount,
                                            'stamp_rule_id' => $stampRuleId,
                                        ]);
                                    }
                                }
                            }
                        }
                    }
                    
                    // Update order with total stamp discount amount
                    $order->update([
                        'stamp_discount_amount' => ($order->stamp_discount_amount ?? 0) + $totalAppliedDiscount,
                    ]);
                    
                    // Check if stamps were already deducted for this order (to prevent double deduction)
                    $stampsAlreadyDeducted = \Modules\Loyalty\Entities\LoyaltyStampTransaction::where('restaurant_id', $restaurantId)
                        ->where('customer_id', $order->customer_id)
                        ->where('stamp_rule_id', $stampRuleId)
                        ->where('order_id', $order->id)
                        ->where('type', 'REDEEM')
                        ->exists();
                    
                    if ($stampsAlreadyDeducted) {
                        // Stamps already deducted, just refresh and mark as applied (won't deduct again)
                        $order->refresh();
                        $rewardApplied = true;
                        break;
                    }
                    
                    // Recalculate order total - discount is already applied to item amounts
                    $order->refresh();
                    $order->load(['items', 'taxes.tax', 'charges.charge']);
                    
                    // Subtotal is sum of item amounts (discounts already deducted from item amounts)
                    $newSubTotal = $order->items->sum('amount');
                    
                    // Apply other discounts (regular and loyalty) - stamp discount is already in item amounts
                    $regularDiscount = $order->discount_amount ?? 0;
                    $loyaltyDiscount = $order->loyalty_discount_amount ?? 0;
                    $totalDiscount = $regularDiscount + $loyaltyDiscount;
                    
                    $discountedSubTotal = $newSubTotal - $totalDiscount;
                    
                    // Step 1: Calculate service charges on discounted subtotal (after all discounts)
                    // Filter charges by order type to ensure only applicable charges are included
                    $orderTypeSlug = optional($order->orderType)->slug ?? ($order->order_type ?? null);
                    $serviceTotal = 0;
                    if ($order->charges) {
                        foreach ($order->charges as $chargeRelation) {
                            $charge = $chargeRelation->charge;
                            if (!$charge) {
                                continue;
                            }
                            
                            // Filter by order type if order type is available
                            if ($orderTypeSlug) {
                                $allowedTypes = $charge->order_types ?? [];
                                
                                // Handle string (JSON) format
                                if (is_string($allowedTypes)) {
                                    $decoded = json_decode($allowedTypes, true);
                                    $allowedTypes = json_last_error() === JSON_ERROR_NONE ? $decoded : [];
                                }
                                
                                // Only add charge if it's applicable to this order type
                                if (is_array($allowedTypes) && !empty($allowedTypes) && !in_array($orderTypeSlug, $allowedTypes, true)) {
                                    continue; // Skip this charge - not applicable to order type
                                }
                            }
                            
                            // Calculate charge amount
                            $chargeAmount = $charge->getAmount($discountedSubTotal);
                            $serviceTotal += $chargeAmount;
                        }
                    }
                    $serviceTotal = round($serviceTotal, 2);
                    
                    // Step 2: Calculate tax_base based on setting
                    // Tax base = (subtotal - discounts) + service charges (if enabled)
                    $includeChargesInTaxBase = $order->branch->restaurant->include_charges_in_tax_base ?? true;
                    $taxBase = $includeChargesInTaxBase ? ($discountedSubTotal + $serviceTotal) : $discountedSubTotal;
                    
                    // Step 3: Recalculate taxes on tax_base (AFTER all discounts and considering service charges)
                    $taxAmount = 0;
                    if ($order->tax_mode === 'order' && $order->taxes) {
                        foreach ($order->taxes as $orderTax) {
                            $tax = $orderTax->tax ?? null;
                            if ($tax) {
                                $taxAmount += ($tax->tax_percent / 100) * max(0, $taxBase);
                            }
                        }
                    } else {
                        $taxAmount = $order->items->sum('tax_amount') ?? 0;
                    }
                    
                    // Step 4: Calculate final total = discounted subtotal + service charges + taxes + tip + delivery
                    $finalTotal = max(0, $discountedSubTotal) + $serviceTotal + $taxAmount;
                    $finalTotal += ($order->tip_amount ?? 0);
                    $finalTotal += ($order->delivery_fee ?? 0);
                    
                    // Preserve loyalty fields when updating order after stamp redemption
                    $updateData = [
                        'sub_total' => $newSubTotal,
                        'total' => round($finalTotal, 2),
                        'total_tax_amount' => round($taxAmount, 2),
                    ];
                    
                    // Preserve loyalty fields if they exist (in case points were also redeemed)
                    if ($order->loyalty_points_redeemed > 0) {
                        $updateData['loyalty_points_redeemed'] = $order->loyalty_points_redeemed;
                    }
                    if ($order->loyalty_discount_amount > 0) {
                        $updateData['loyalty_discount_amount'] = $order->loyalty_discount_amount;
                    }
                    
                    $order->update($updateData);
                    $rewardApplied = true;
                    break;
            }

            // Only deduct stamps if reward was successfully applied
            if (!$rewardApplied) {
                DB::rollBack();
                return [
                    'success' => false,
                    'message' => __('loyalty::app.failedToRedeemStamps', ['error' => 'Reward could not be applied']),
                ];
            }

            // Update customer stamp - deduct stamps for all items being redeemed
            $customerStamp->stamps_redeemed += $totalStampsNeeded;
            $customerStamp->last_redeemed_at = now();
            $customerStamp->save();

            // Create transaction records - one per item being redeemed
            for ($i = 0; $i < $itemsToRedeem; $i++) {
                LoyaltyStampTransaction::create([
                    'restaurant_id' => $restaurantId,
                    'customer_id' => $order->customer_id,
                    'stamp_rule_id' => $stampRuleId,
                    'order_id' => $order->id,
                    'type' => 'REDEEM',
                    'stamps' => -$stampRule->stamps_required,
                    'reason' => __('loyalty::app.stampsRedeemedForOrder', [
                        'order_number' => $order->order_number,
                    ]),
                ]);
            }

            // Reload order to get updated items/discounts
            $order->refresh();
            $order->load('items');

            DB::commit();

            return [
                'success' => true,
                'stamps_redeemed' => $stampRule->stamps_required,
                'reward_value' => $rewardValue,
                'reward_type' => $stampRule->reward_type,
                'reward_menu_item_id' => $stampRule->reward_menu_item_id,
                'reward_menu_item_variation_id' => $stampRule->reward_menu_item_variation_id,
                'message' => __('loyalty::app.stampsRedeemedSuccessfully'),
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Loyalty: Failed to redeem stamps', [
                'order_id' => $order->id,
                'stamp_rule_id' => $stampRuleId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => __('loyalty::app.failedToRedeemStamps', ['error' => $e->getMessage()]),
            ];
        }
    }

    /**
     * Load tier information for a customer
     * Returns array with tier data that can be used in components
     */
    public function getTierInformation($restaurantId, $customerId): array
    {
        $result = [
            'currentTier' => null,
            'nextTier' => null,
            'pointsToNextTier' => null,
            'tierProgress' => 0,
        ];

        if (!class_exists(LoyaltyTier::class)) {
            return $result;
        }

        try {
            // Get loyalty account
            $account = $this->getOrCreateAccount($restaurantId, $customerId);
            $pointsBalance = $account->points_balance;

            // Get current tier
            $currentTier = LoyaltyTier::getTierForPoints($restaurantId, $pointsBalance);
            $result['currentTier'] = $currentTier;

            if ($currentTier) {
                // Get next tier
                $nextTier = $currentTier->getNextTier();
                $result['nextTier'] = $nextTier;

                if ($nextTier) {
                    $pointsToNextTier = $currentTier->getPointsToNextTier($pointsBalance);
                    $result['pointsToNextTier'] = $pointsToNextTier;

                    // Calculate progress percentage
                    $pointsInCurrentTier = $pointsBalance - $currentTier->min_points;
                    $pointsNeededForNextTier = $nextTier->min_points - $currentTier->min_points;
                    if ($pointsNeededForNextTier > 0) {
                        $result['tierProgress'] = min(100, ($pointsInCurrentTier / $pointsNeededForNextTier) * 100);
                    }
                } else {
                    // Already at highest tier
                    $result['tierProgress'] = 100;
                }
            }
        } catch (\Exception $e) {
            //
        }

        return $result;
    }
}
