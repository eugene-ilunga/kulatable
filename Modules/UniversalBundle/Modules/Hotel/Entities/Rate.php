<?php

namespace Modules\Hotel\Entities;

use App\Models\BaseModel;
use App\Models\Restaurant;
use App\Models\Branch;
use App\Traits\HasBranch;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Rate extends BaseModel
{
    use HasBranch;

    protected $table = 'hotel_rates';

    protected $guarded = ['id'];

    protected $casts = [
        'date_from' => 'date',
        'date_to' => 'date',
        'rate' => 'decimal:2',
        'single_occupancy_rate' => 'decimal:2',
        'double_occupancy_rate' => 'decimal:2',
        'extra_person_rate' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class, 'room_type_id');
    }

    public function ratePlan(): BelongsTo
    {
        return $this->belongsTo(RatePlan::class, 'rate_plan_id');
    }
}
