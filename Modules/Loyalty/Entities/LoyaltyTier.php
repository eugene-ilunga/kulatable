<?php

namespace Modules\Loyalty\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LoyaltyTier extends Model
{
    protected $table = 'loyalty_tiers';
    
    protected $guarded = ['id'];

    protected $casts = [
        'min_points' => 'integer',
        'max_points' => 'integer',
        'earning_multiplier' => 'decimal:2',
        'redemption_multiplier' => 'decimal:2',
        'order' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Get the restaurant that owns the tier.
     */
    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Restaurant::class);
    }

    /**
     * Get all loyalty accounts in this tier.
     */
    public function loyaltyAccounts(): HasMany
    {
        return $this->hasMany(LoyaltyAccount::class);
    }

    /**
     * Get the tier for a given points balance.
     */
    public static function getTierForPoints($restaurantId, $points): ?self
    {
        return LoyaltyTier::where('restaurant_id', $restaurantId)
            ->where('is_active', true)
            ->where('min_points', '<=', $points)
            ->where(function ($query) use ($points) {
                $query->whereNull('max_points')
                    ->orWhere('max_points', '>=', $points);
            })
            ->orderBy('min_points', 'desc')
            ->first();
    }

    /**
     * Get all active tiers for a restaurant, ordered by min_points.
     */
    public static function getActiveTiersForRestaurant($restaurantId)
    {
        return static::where('restaurant_id', $restaurantId)
            ->where('is_active', true)
            ->orderBy('min_points', 'asc')
            ->get();
    }

    /**
     * Get the next tier (if exists).
     */
    public function getNextTier(): ?self
    {
        return static::where('restaurant_id', $this->restaurant_id)
            ->where('is_active', true)
            ->where('min_points', '>', $this->min_points)
            ->orderBy('min_points', 'asc')
            ->first();
    }

    /**
     * Get the previous tier (if exists).
     */
    public function getPreviousTier(): ?self
    {
        return static::where('restaurant_id', $this->restaurant_id)
            ->where('is_active', true)
            ->where('min_points', '<', $this->min_points)
            ->orderBy('min_points', 'desc')
            ->first();
    }

    /**
     * Calculate points needed to reach next tier.
     */
    public function getPointsToNextTier($currentPoints): ?int
    {
        $nextTier = $this->getNextTier();
        if (!$nextTier) {
            return null;
        }
        return max(0, $nextTier->min_points - $currentPoints);
    }

    /**
     * Check if customer qualifies for this tier.
     */
    public function qualifiesForTier($points): bool
    {
        if ($points < $this->min_points) {
            return false;
        }
        if ($this->max_points !== null && $points > $this->max_points) {
            return false;
        }
        return true;
    }
}
