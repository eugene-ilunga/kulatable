<?php

namespace Modules\Aitools\Entities;

use App\Models\BaseModel;
use App\Traits\HasRestaurant;
use App\Models\Restaurant;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AiUsageDaily extends BaseModel
{
    use HasFactory, HasRestaurant;

    protected $table = 'ai_usage_daily';

    protected $guarded = ['id'];

    protected $casts = [
        'date' => 'date',
    ];

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }
}
