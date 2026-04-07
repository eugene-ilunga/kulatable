<?php

namespace Modules\Sms\Entities;

use Illuminate\Database\Eloquent\Model;

class SmsTemplate extends Model
{
    protected $table = 'sms_templates';
    protected $fillable = [
        'type',
        'flow_id',
    ];

    protected $casts = [
        'flow_id' => 'string',
    ];
} 