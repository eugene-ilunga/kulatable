<?php

namespace Modules\Sms\Entities;

use App\Models\BaseModel;
use App\Models\Restaurant;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RestaurantAndroidSmsSetting extends BaseModel
{
    protected $table = 'restaurant_android_sms_settings';

    protected $guarded = ['id'];

    protected $fillable = [
        'restaurant_id',
        'base_url',
        'username',
        'password',
    ];

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }
}
