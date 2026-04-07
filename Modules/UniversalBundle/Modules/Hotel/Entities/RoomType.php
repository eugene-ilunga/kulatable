<?php

namespace Modules\Hotel\Entities;

use App\Models\BaseModel;
use App\Models\Restaurant;
use App\Models\Branch;
use App\Traits\HasBranch;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;

class RoomType extends BaseModel
{
    use HasBranch;

    protected $table = 'hotel_room_types';

    protected $guarded = ['id'];

    protected $casts = [
        'amenities' => 'array',
        'is_active' => 'boolean',
        'max_occupancy' => 'integer',
        'base_occupancy' => 'integer',
        'base_rate' => 'decimal:2',
        'sort_order' => 'integer',
    ];

    protected $appends = [
        'image_url',
    ];

    public function imageUrl(): Attribute
    {
        return Attribute::get(function (): string {
            if ($this->image) {
                return asset_url_local_s3('room_type/' . $this->image);
            }

            $defaults = [
                'Standard Room' => asset('img/room-standard.png'),
                'Deluxe Room'   => asset('img/room-deluxe.png'),
                'Suite'         => asset('img/room-suite.png'),
            ];

            return $defaults[$this->name] ?? asset('img/room.png');
        });
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class, 'room_type_id');
    }

    public function rates(): HasMany
    {
        return $this->hasMany(Rate::class, 'room_type_id');
    }

    public function reservationRooms(): HasMany
    {
        return $this->hasMany(ReservationRoom::class, 'room_type_id');
    }
}
