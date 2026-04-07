<?php

namespace Modules\Hotel\Entities;

use App\Models\BaseModel;
use App\Models\Restaurant;
use App\Models\Branch;
use App\Traits\HasBranch;
use Modules\Hotel\Enums\RatePlanType;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RatePlan extends BaseModel
{
    use HasBranch;

    protected $table = 'hotel_rate_plans';

    protected $guarded = ['id'];

    protected $casts = [
        'type' => RatePlanType::class,
        'is_active' => 'boolean',
        'cancellation_hours' => 'integer',
        'cancellation_charge_percent' => 'decimal:2',
    ];

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function rates(): HasMany
    {
        return $this->hasMany(Rate::class, 'rate_plan_id');
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class, 'rate_plan_id');
    }
}
