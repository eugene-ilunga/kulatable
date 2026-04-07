<?php

namespace Modules\Hotel\Entities;

use App\Models\BaseModel;
use App\Models\Restaurant;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\User;
use App\Traits\HasBranch;
use Modules\Hotel\Enums\EventStatus;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends BaseModel
{
    use HasBranch;

    protected $table = 'hotel_events';

    protected $guarded = ['id'];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'status' => EventStatus::class,
        'expected_guests' => 'integer',
        'package_amount' => 'decimal:2',
        'advance_paid' => 'decimal:2',
    ];

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class, 'venue_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function eventCharges(): HasMany
    {
        return $this->hasMany(EventCharge::class, 'event_id');
    }

    /**
     * Generate a unique event number
     */
    public static function generateEventNumber($branchId): string
    {
        $prefix = 'EVT';
        $year = date('Y');
        $month = date('m');
        
        $lastEvent = self::where('branch_id', $branchId)
            ->where('event_number', 'like', "{$prefix}-{$year}{$month}-%")
            ->orderBy('id', 'desc')
            ->first();
        
        if ($lastEvent) {
            $lastNumber = (int) substr($lastEvent->event_number, -4);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }
        
        return sprintf('%s-%s%s-%04d', $prefix, $year, $month, $nextNumber);
    }
}
