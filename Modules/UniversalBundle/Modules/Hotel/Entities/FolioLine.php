<?php

namespace Modules\Hotel\Entities;

use App\Models\BaseModel;
use App\Models\User;
use Modules\Hotel\Enums\FolioLineType;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class FolioLine extends BaseModel
{
    protected $table = 'hotel_folio_lines';

    protected $guarded = ['id'];

    protected $casts = [
        'type' => FolioLineType::class,
        'amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'posting_date' => 'date',
    ];

    public function folio(): BelongsTo
    {
        return $this->belongsTo(Folio::class, 'folio_id');
    }

    public function postedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function reference(): MorphTo
    {
        return $this->morphTo('reference', 'reference_type', 'reference_id');
    }
}
