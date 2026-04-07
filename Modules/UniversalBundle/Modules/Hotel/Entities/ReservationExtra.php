<?php

namespace Modules\Hotel\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReservationExtra extends BaseModel
{
    protected $table = 'hotel_reservation_extras';

    protected $guarded = ['id'];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class, 'reservation_id');
    }

    public function extraService(): BelongsTo
    {
        return $this->belongsTo(ExtraService::class, 'extra_service_id');
    }
}
