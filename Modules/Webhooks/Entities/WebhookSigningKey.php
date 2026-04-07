<?php

namespace Modules\Webhooks\Entities;

use App\Models\Restaurant;
use App\Traits\HasRestaurant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebhookSigningKey extends Model
{
    use HasFactory;
    use HasRestaurant;

    protected $guarded = ['id'];

    protected $casts = [
        'rotated_at' => 'datetime',
    ];

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }
}
