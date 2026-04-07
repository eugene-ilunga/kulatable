<?php

namespace Modules\Loyalty\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoyaltyStampTransaction extends Model
{
    protected $table = 'loyalty_stamp_transactions';
    
    protected $guarded = ['id'];

    protected $casts = [
        'stamps' => 'integer',
        'expires_at' => 'datetime',
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
     * Get the order (if applicable).
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Order::class);
    }
}
