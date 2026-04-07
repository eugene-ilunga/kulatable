<?php

namespace Modules\Loyalty\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LoyaltyAccount extends Model
{
    protected $table = 'loyalty_accounts';
    
    protected $guarded = ['id'];

    protected $casts = [
        'points_balance' => 'integer',
    ];

    /**
     * Get the restaurant that owns the loyalty account.
     */
    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Restaurant::class);
    }

    /**
     * Get the customer that owns the loyalty account.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Customer::class);
    }

    /**
     * Get the tier for this account.
     */
    public function tier(): BelongsTo
    {
        return $this->belongsTo(LoyaltyTier::class, 'tier_id');
    }

    /**
     * Get all ledger entries for this account.
     */
    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(LoyaltyLedger::class, 'customer_id', 'customer_id')
            ->where('restaurant_id', $this->restaurant_id);
    }

    /**
     * Update points balance (should be called after ledger entry is created).
     */
    public function updateBalance(): void
    {
        $balance = LoyaltyLedger::where('restaurant_id', $this->restaurant_id)
            ->where('customer_id', $this->customer_id)
            ->sum('points');
        
        $this->points_balance = $balance;
        $this->save();
    }

    /**
     * Update tier based on current points balance.
     */
    public function updateTier(): void
    {
        $tier = LoyaltyTier::getTierForPoints($this->restaurant_id, $this->points_balance);
        
        if ($tier && $this->tier_id != $tier->id) {
            $this->tier_id = $tier->id;
            $this->save();
        } elseif (!$tier && $this->tier_id !== null) {
            // No tier found, clear tier
            $this->tier_id = null;
            $this->save();
        }
    }

    /**
     * Get current tier or default tier.
     */
    public function getCurrentTier(): ?LoyaltyTier
    {
        if ($this->tier_id) {
            return $this->tier;
        }
        
        // Get default tier (lowest tier)
        return LoyaltyTier::where('restaurant_id', $this->restaurant_id)
            ->where('is_active', true)
            ->orderBy('min_points', 'asc')
            ->first();
    }
}

