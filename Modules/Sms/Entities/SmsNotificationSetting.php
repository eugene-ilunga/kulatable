<?php

namespace Modules\Sms\Entities;

use App\Models\BaseModel;
use App\Models\ModuleSetting;
use App\Scopes\CompanyScope;
use Modules\Sms\Enums\SmsNotificationSlug;
use App\Models\Restaurant;
use App\Models\User;
use App\Traits\HasRestaurant;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class SmsNotificationSetting extends BaseModel
{
    use HasFactory, HasRestaurant;

    protected $guarded = ['id'];
    protected $casts = [
        'type' => 'string',
    ];


}
