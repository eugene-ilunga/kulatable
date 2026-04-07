<?php

namespace Modules\Loyalty\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerStamp extends Model
{
    protected $table = 'customer_stamps';
    
    protected $guarded = ['id'];

    protected $casts = [
        'stamps_earned' => 'integer',
        'stamps_redeemed' => 'integer',
        'last_earned_at' => 'datetime',
        'last_redeemed_at' => 'datetime',
    ];

    /**
     * Get the restaurant.
     */
    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Restaurant::class);
    }

    /**
     * Get the customer.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Customer::class);
    }

    /**
     * Get the stamp rule.
     */
    public function stampRule(): BelongsTo
    {
        return $this->belongsTo(LoyaltyStampRule::class, 'stamp_rule_id');
    }

    /**
     * Get current available stamps (earned - redeemed).
     */
    public function getAvailableStampsAttribute(): int
    {
        return max(0, $this->stamps_earned - $this->stamps_redeemed);
    }

    /**
     * Check if customer can redeem for this rule.
     */
    public function canRedeem(): bool
    {
        return $this->getAvailableStampsAttribute() >= $this->stampRule->stamps_required;
    }

    /**
     * Get or create customer stamp record.
     */
    public static function getOrCreate($restaurantId, $customerId, $stampRuleId): self
    {
        return static::firstOrCreate(
            [
                'restaurant_id' => $restaurantId,
                'customer_id' => $customerId,
                'stamp_rule_id' => $stampRuleId,
            ],
            [
                'stamps_earned' => 0,
                'stamps_redeemed' => 0,
            ]
        );
    }
}
