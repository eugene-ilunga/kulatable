<?php

namespace Modules\Hotel\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class HotelGlobalSetting extends BaseModel
{
    use HasFactory;

    protected $guarded = ['id'];

    const MODULE_NAME = 'hotel';

    protected $table = 'hotel_global_settings';

    protected $fillable = [
        'license_type',
        'purchase_code',
        'purchased_on',
        'supported_until',
        'notify_update',
    ];

    protected $casts = [
        'notify_update' => 'boolean',
        'purchased_on' => 'datetime',
        'supported_until' => 'datetime',
    ];
}
