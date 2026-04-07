<?php

namespace Modules\Hotel\Entities;

use App\Models\BaseModel;
use App\Models\Restaurant;
use App\Models\Branch;
use App\Models\Customer;
use App\Traits\HasBranch;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Guest extends BaseModel
{
    use HasBranch;

    protected $table = 'hotel_guests';

    protected $guarded = ['id'];

    protected $casts = [
        'date_of_birth' => 'date',
        'preferences' => 'array',
    ];

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class, 'primary_guest_id');
    }

    public function stayGuests(): HasMany
    {
        return $this->hasMany(StayGuest::class, 'guest_id');
    }

    public function reservationGuests(): HasMany
    {
        return $this->hasMany(ReservationGuest::class, 'guest_id');
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name . ' ' . ($this->last_name ?? ''));
    }
}
