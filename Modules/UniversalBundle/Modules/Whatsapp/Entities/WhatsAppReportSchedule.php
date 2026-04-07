<?php

namespace Modules\Whatsapp\Entities;

use Illuminate\Database\Eloquent\Model;

class WhatsAppReportSchedule extends Model
{
    protected $table = 'whatsapp_report_schedules';
    
    protected $guarded = ['id'];

    protected $casts = [
        'roles' => 'array',
        'is_enabled' => 'boolean',
        'last_sent_at' => 'datetime',
    ];

    /**
     * Get restaurant.
     */
    public function restaurant()
    {
        return $this->belongsTo(\App\Models\Restaurant::class);
    }
}

