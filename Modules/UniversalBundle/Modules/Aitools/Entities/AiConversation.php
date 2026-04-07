<?php

namespace Modules\Aitools\Entities;

use App\Models\BaseModel;
use App\Traits\HasRestaurant;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Restaurant;
use App\Models\User;

class AiConversation extends BaseModel
{
    use HasFactory;
    use HasRestaurant;

    protected $guarded = ['id'];

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(AiMessage::class, 'conversation_id')->orderBy('created_at');
    }
}
