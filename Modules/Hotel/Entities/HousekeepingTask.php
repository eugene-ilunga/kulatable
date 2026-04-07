<?php

namespace Modules\Hotel\Entities;

use App\Models\BaseModel;
use App\Models\Restaurant;
use App\Models\Branch;
use App\Models\User;
use App\Traits\HasBranch;
use Modules\Hotel\Enums\HousekeepingTaskType;
use Modules\Hotel\Enums\HousekeepingTaskStatus;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HousekeepingTask extends BaseModel
{
    use HasBranch;

    protected $table = 'hotel_housekeeping_tasks';

    protected $guarded = ['id'];

    protected $casts = [
        'task_date' => 'date',
        'type' => HousekeepingTaskType::class,
        'status' => HousekeepingTaskStatus::class,
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class, 'room_id');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }
}
