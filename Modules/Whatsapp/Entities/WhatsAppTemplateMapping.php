<?php

namespace Modules\Whatsapp\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsAppTemplateMapping extends Model
{
    protected $table = 'whatsapp_template_mappings';
    
    protected $guarded = ['id'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the restaurant that owns the template mapping.
     */
    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Restaurant::class);
    }

    /**
     * Get the template definition.
     */
    public function definition(): BelongsTo
    {
        return $this->belongsTo(WhatsAppTemplateDefinition::class, 'notification_type', 'notification_type');
    }

    /**
     * Scope to get active mappings.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get mappings for a specific notification type.
     */
    public function scopeForNotificationType($query, string $notificationType)
    {
        return $query->where('notification_type', $notificationType);
    }
}

