<?php

namespace Modules\Hotel\Entities;

use App\Models\BaseModel;
use App\Models\Restaurant;
use App\Models\Branch;
use App\Models\User;
use App\Traits\HasBranch;
use Modules\Hotel\Enums\ReservationStatus;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Reservation extends BaseModel
{
    use HasBranch;

    protected $table = 'hotel_reservations';

    protected $guarded = ['id'];

    protected $casts = [
        'check_in_date' => 'date',
        'check_out_date' => 'date',
        'check_in_time' => 'datetime',
        'check_out_time' => 'datetime',
        'status' => ReservationStatus::class,
        'rooms_count' => 'integer',
        'adults' => 'integer',
        'children' => 'integer',
        'total_amount' => 'decimal:2',
        'advance_paid' => 'decimal:2',
        'cancelled_at' => 'datetime',
        'discount_value' => 'decimal:2',
        'subtotal_before_tax' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'extras_amount' => 'decimal:2',
    ];

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function primaryGuest(): BelongsTo
    {
        return $this->belongsTo(Guest::class, 'primary_guest_id');
    }

    public function ratePlan(): BelongsTo
    {
        return $this->belongsTo(RatePlan::class, 'rate_plan_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function reservationRooms(): HasMany
    {
        return $this->hasMany(ReservationRoom::class, 'reservation_id');
    }

    public function tax(): BelongsTo
    {
        return $this->belongsTo(Tax::class, 'tax_id');
    }

    public function reservationGuests(): HasMany
    {
        return $this->hasMany(ReservationGuest::class, 'reservation_id')->orderBy('sort_order');
    }

    public function reservationExtras(): HasMany
    {
        return $this->hasMany(ReservationExtra::class, 'reservation_id');
    }

    public function stays(): HasMany
    {
        return $this->hasMany(Stay::class, 'reservation_id');
    }

    /**
     * Generate a unique reservation number
     */
    public static function generateReservationNumber($branchId): string
    {
        $prefix = 'RES';
        $year = date('Y');
        $month = date('m');
        
        $lastReservation = self::where('branch_id', $branchId)
            ->where('reservation_number', 'like', "{$prefix}-{$year}{$month}-%")
            ->orderBy('id', 'desc')
            ->first();
        
        if ($lastReservation) {
            $lastNumber = (int) substr($lastReservation->reservation_number, -4);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }
        
        return sprintf('%s-%s%s-%04d', $prefix, $year, $month, $nextNumber);
    }
}
