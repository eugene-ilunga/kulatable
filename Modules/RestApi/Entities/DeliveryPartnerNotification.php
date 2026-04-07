<?php

namespace Modules\RestApi\Entities;

use Illuminate\Database\Eloquent\Model;

class DeliveryPartnerNotification extends Model
{
    protected $table = 'delivery_partner_notifications';

    protected $guarded = ['id'];

    protected $casts = [
        'data' => 'array',
        'is_sent' => 'boolean',
        'sent_at' => 'datetime',
    ];
}

