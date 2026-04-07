<?php

namespace Modules\RestApi\Entities;

use App\Models\BaseModel;
use App\Models\Branch;
use App\Models\DeliveryExecutive;
use App\Models\Order;
use App\Models\Restaurant;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryExecutiveLocation extends BaseModel
{
    protected $table = 'delivery_executive_locations';

    protected $fillable = [
        'delivery_executive_id',
        'order_id',
        'restaurant_id',
        'branch_id',
        'latitude',
        'longitude',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    public function deliveryExecutive(): BelongsTo
    {
        return $this->belongsTo(DeliveryExecutive::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
