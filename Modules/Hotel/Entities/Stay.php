<?php

namespace Modules\Hotel\Entities;

use App\Models\BaseModel;
use App\Models\Restaurant;
use App\Models\Branch;
use App\Models\User;
use App\Traits\HasBranch;
use Modules\Hotel\Enums\StayStatus;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Stay extends BaseModel
{
    use HasBranch;

    protected $table = 'hotel_stays';

    protected $guarded = ['id'];

    protected $casts = [
        'check_in_at' => 'datetime',
        'expected_checkout_at' => 'datetime',
        'actual_checkout_at' => 'datetime',
        'status' => StayStatus::class,
        'adults' => 'integer',
        'children' => 'integer',
        'credit_limit' => 'decimal:2',
    ];

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class, 'reservation_id');
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class, 'room_id');
    }

    public function checkedInBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_in_by');
    }

    public function checkedOutBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_out_by');
    }

    public function stayGuests(): HasMany
    {
        return $this->hasMany(StayGuest::class, 'stay_id');
    }

    public function folio(): HasOne
    {
        return $this->hasOne(Folio::class, 'stay_id');
    }

    /**
     * Generate a unique stay number
     */
    public static function generateStayNumber($branchId): string
    {
        $prefix = 'STAY';
        $year = date('Y');
        $month = date('m');
        
        $lastStay = self::where('branch_id', $branchId)
            ->where('stay_number', 'like', "{$prefix}-{$year}{$month}-%")
            ->orderBy('id', 'desc')
            ->first();
        
        if ($lastStay) {
            $lastNumber = (int) substr($lastStay->stay_number, -4);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }
        
        return sprintf('%s-%s%s-%04d', $prefix, $year, $month, $nextNumber);
    }
}
