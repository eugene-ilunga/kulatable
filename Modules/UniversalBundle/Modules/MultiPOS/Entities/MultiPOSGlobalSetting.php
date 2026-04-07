<?php

namespace Modules\MultiPOS\Entities;

use App\Models\BaseModel;

class MultiPOSGlobalSetting extends BaseModel
{

    const MODULE_NAME = 'MultiPOS';

    protected $table = 'multi_pos_global_settings';
    protected $guarded = [];


    protected $casts = [
        'purchased_on' => 'datetime',
        'supported_until' => 'datetime',
    ];
}
