<?php

namespace Modules\RestApi\Entities;

use App\Models\DeliveryExecutive;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryPartnerDeviceToken extends Model
{
    protected $table = 'delivery_partner_device_tokens';

    protected $guarded = ['id'];

    public function deliveryExecutive(): BelongsTo
    {
        return $this->belongsTo(DeliveryExecutive::class, 'delivery_executive_id');
    }
}
