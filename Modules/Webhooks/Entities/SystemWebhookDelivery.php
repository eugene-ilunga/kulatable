<?php

namespace Modules\Webhooks\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Delivery log for system-level webhooks.
 */
class SystemWebhookDelivery extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'payload' => 'array',
        'attempts' => 'integer',
        'response_code' => 'integer',
        'duration_ms' => 'integer',
        'next_retry_at' => 'datetime',
    ];

    /**
     * Get the parent webhook.
     */
    public function webhook(): BelongsTo
    {
        return $this->belongsTo(SystemWebhook::class, 'system_webhook_id');
    }
}
