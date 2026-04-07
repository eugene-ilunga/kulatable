<?php

namespace Modules\Whatsapp\Entities;

use Illuminate\Database\Eloquent\Model;

class WhatsappGlobalSetting extends Model
{
    protected $table = 'whatsapp_global_settings';

    protected $guarded = ['id'];

    protected $casts = [
        'supported_until' => 'datetime',
        'purchased_on' => 'datetime',
    ];
}   
