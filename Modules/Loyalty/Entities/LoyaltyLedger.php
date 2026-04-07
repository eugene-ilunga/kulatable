<?php

namespace Modules\Loyalty\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoyaltyLedger extends Model
{
    protected $table = 'loyalty_ledger';
    
    protected $guarded = ['id'];

    protected $casts = [
        'points' => 'integer',
        'expires_at' => 'datetime',
    ];

    /**
     * Get the restaurant that owns the ledger entry.
     */
    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Restaurant::class);
    }

    /**
     * Get the customer that owns the ledger entry.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Customer::class);
    }

    /**
     * Get the order associated with the ledger entry.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Order::class);
    }

    /**
     * Scope to get EARN entries.
     */
    public function scopeEarned($query)
    {
        return $query->where('type', 'EARN');
    }

    /**
     * Scope to get REDEEM entries.
     */
    public function scopeRedeemed($query)
    {
        return $query->where('type', 'REDEEM');
    }
}

