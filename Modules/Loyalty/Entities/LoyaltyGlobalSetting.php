<?php

namespace Modules\Loyalty\Entities;

use Illuminate\Database\Eloquent\Model;


class LoyaltyGlobalSetting extends Model
{
    protected $table = 'loyalty_global_settings';

    const MODULE_NAME = 'loyalty';

    protected $guarded = ['id'];
}
