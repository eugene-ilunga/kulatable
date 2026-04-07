<?php

namespace Modules\Webhooks\Entities;

use App\Models\Branch;
use App\Models\Restaurant;
use App\Traits\HasRestaurant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Webhook extends Model
{
    use HasFactory;
    use HasRestaurant;

    protected $guarded = ['id'];

    protected $casts = [
        'is_active' => 'boolean',
        'max_attempts' => 'integer',
        'backoff_seconds' => 'integer',
        'subscribed_events' => 'array',
        'source_modules' => 'array',
        'custom_headers' => 'array',
        'redact_payload' => 'boolean',
    ];

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(WebhookDelivery::class);
    }
}
