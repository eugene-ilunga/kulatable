<?php

namespace Modules\Webhooks\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * System-level webhooks that receive events from all tenants.
 * These are managed exclusively by Super Admin.
 */
class SystemWebhook extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'is_active' => 'boolean',
        'max_attempts' => 'integer',
        'backoff_seconds' => 'integer',
        'subscribed_events' => 'array',
        'custom_headers' => 'array',
    ];

    /**
     * Get deliveries for this system webhook.
     */
    public function deliveries(): HasMany
    {
        return $this->hasMany(SystemWebhookDelivery::class);
    }

    /**
     * Check if this webhook is subscribed to a given event.
     */
    public function isSubscribedTo(string $event): bool
    {
        // If subscribed_events is null or empty, subscribe to all events
        if (empty($this->subscribed_events)) {
            return true;
        }

        return in_array($event, $this->subscribed_events);
    }

    /**
     * Scope to get only active webhooks.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
