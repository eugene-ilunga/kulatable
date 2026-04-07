<?php

namespace Modules\FontControl\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FontControlSetting extends BaseModel
{
    use HasFactory;

    protected $table = 'font_control_settings';

    protected $guarded = ['id'];

    protected $casts = [
        'font_size' => 'integer',
    ];
}
