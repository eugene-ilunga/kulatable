<?php

namespace Modules\Loyalty\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LoyaltyStampRule extends Model
{
    protected $table = 'loyalty_stamp_rules';
    
    protected $guarded = ['id'];

    protected $casts = [
        'stamps_required' => 'integer',
        'reward_value' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Get the restaurant that owns the stamp rule.
     */
    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Restaurant::class);
    }

    /**
     * Get the menu item for this stamp rule.
     */
    public function menuItem(): BelongsTo
    {
        return $this->belongsTo(\App\Models\MenuItem::class, 'menu_item_id');
    }

    /**
     * Get the reward menu item (for free_item reward type).
     */
    public function rewardMenuItem(): BelongsTo
    {
        return $this->belongsTo(\App\Models\MenuItem::class, 'reward_menu_item_id');
    }

    /**
     * Get the reward menu item variation (for free_item reward type with variations).
     */
    public function rewardMenuItemVariation(): BelongsTo
    {
        return $this->belongsTo(\App\Models\MenuItemVariation::class, 'reward_menu_item_variation_id');
    }

    /**
     * Get all customer stamps for this rule.
     */
    public function customerStamps(): HasMany
    {
        return $this->hasMany(CustomerStamp::class, 'stamp_rule_id');
    }

    /**
     * Get all stamp transactions for this rule.
     */
    public function stampTransactions(): HasMany
    {
        return $this->hasMany(LoyaltyStampTransaction::class, 'stamp_rule_id');
    }

    /**
     * Get active stamp rules for a restaurant.
     */
    public static function getActiveRulesForRestaurant($restaurantId)
    {
        return static::where('restaurant_id', $restaurantId)
            ->where('is_active', true)
            ->with(['menuItem', 'rewardMenuItem', 'rewardMenuItemVariation'])
            ->get();
    }

    /**
     * Get stamp rule for a specific menu item.
     */
    public static function getRuleForMenuItem($restaurantId, $menuItemId): ?self
    {
        return static::where('restaurant_id', $restaurantId)
            ->where('menu_item_id', $menuItemId)
            ->where('is_active', true)
            ->with(['menuItem', 'rewardMenuItem', 'rewardMenuItemVariation'])
            ->first();
    }

    /**
     * Calculate reward value for redemption.
     */
    public function calculateRewardValue($orderSubtotal = 0): float
    {
        switch ($this->reward_type) {
            case 'free_item':
                return $this->rewardMenuItem ? (float)($this->rewardMenuItem->price ?? 0) : 0;
            case 'discount_percent':
                return ($orderSubtotal * $this->reward_value) / 100;
            case 'discount_amount':
                return (float)$this->reward_value;
            default:
                return 0;
        }
    }
}
