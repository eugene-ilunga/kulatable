<?php

namespace Modules\Kiosk\Entities;

use App\Models\BaseModel;
use App\Traits\HasBranch;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Restaurant;
use App\Models\Branch;

class Kiosk extends BaseModel
{
    use HasBranch;

    protected $guarded = ['id'];

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
