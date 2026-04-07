<?php

namespace Modules\Whatsapp\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsAppNotificationLog extends Model
{
    protected $table = 'whatsapp_notification_logs';
    
    protected $guarded = ['id'];

    protected $casts = [
        'variables' => 'array',
        'sent_at' => 'datetime',
    ];

    /**
     * Get the restaurant that owns the notification log.
     */
    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Restaurant::class);
    }

    /**
     * Scope to get sent notifications.
     */
    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    /**
     * Scope to get failed notifications.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope to get pending notifications.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope to filter by notification type.
     */
    public function scopeByNotificationType($query, string $notificationType)
    {
        return $query->where('notification_type', $notificationType);
    }

    /**
     * Mark notification as sent.
     */
    public function markAsSent(string $messageId): void
    {
        $this->update([
            'status' => 'sent',
            'whatsapp_message_id' => $messageId,
            'sent_at' => now(),
        ]);
    }

    /**
     * Mark notification as failed.
     */
    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
        ]);
    }
}

