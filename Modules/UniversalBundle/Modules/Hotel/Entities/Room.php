<?php

namespace Modules\Hotel\Entities;

use App\Models\BaseModel;
use App\Models\Restaurant;
use App\Models\Branch;
use App\Traits\HasBranch;
use Modules\Hotel\Enums\RoomStatus;
use Modules\Hotel\Enums\StayStatus;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Room extends BaseModel
{
    use HasBranch;

    protected $table = 'hotel_rooms';

    protected $guarded = ['id'];

    protected $casts = [
        'status' => RoomStatus::class,
        'is_active' => 'boolean',
    ];

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class, 'room_type_id');
    }

    public function stays(): HasMany
    {
        return $this->hasMany(Stay::class, 'room_id');
    }

    public function currentStay()
    {
        return $this->hasOne(Stay::class, 'room_id')
            ->where('status', StayStatus::CHECKED_IN->value)
            ->latest();
    }

    public function housekeepingTasks(): HasMany
    {
        return $this->hasMany(HousekeepingTask::class, 'room_id');
    }

    public function reservationRooms(): HasMany
    {
        return $this->hasMany(ReservationRoom::class, 'room_id');
    }
}
