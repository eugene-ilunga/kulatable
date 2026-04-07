<?php

namespace Modules\FontControl\Entities;

use App\Models\BaseModel;


class FontControlGlobalSetting extends BaseModel
{

    /**
     * The attributes that are mass assignable.
     */
    protected $guarded = ['id'];

    const MODULE_NAME = 'fontcontrol';

    protected $table = 'font_control_global_settings';
}
