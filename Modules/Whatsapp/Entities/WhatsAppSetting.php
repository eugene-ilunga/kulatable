<?php

namespace Modules\Whatsapp\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class WhatsAppSetting extends Model
{
    protected $table = 'whatsapp_settings';
    
    protected $guarded = ['id'];

    protected $casts = [
        'is_enabled' => 'boolean',
        'webhook_verified_at' => 'datetime',
    ];

    /**
     * Get the restaurant that owns the WhatsApp setting.
     */
    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Restaurant::class);
    }

    /**
     * Encrypt access token when setting.
     */
    public function setAccessTokenAttribute($value): void
    {
        $this->attributes['access_token'] = $value ? Crypt::encryptString($value) : null;
    }

    /**
     * Decrypt access token when getting.
     */
    public function getAccessTokenAttribute($value): ?string
    {
        return $value ? Crypt::decryptString($value) : null;
    }

    /**
     * Check if WhatsApp is configured and enabled.
     */
    public function isConfigured(): bool
    {
        return $this->is_enabled 
            && !empty($this->waba_id) 
            && !empty($this->access_token) 
            && !empty($this->phone_number_id);
    }
}

