<?php

namespace Modules\FontControl\Entities;

use App\Models\BaseModel;

class FontControlQrSetting extends BaseModel
{
    protected $table = 'font_control_qr_settings';

    protected $guarded = ['id'];

    protected $casts = [
        'qr_round_block_size' => 'boolean',
        'qr_size' => 'integer',
        'qr_margin' => 'integer',
        'font_size' => 'integer',
        'advanced_qr_enabled' => 'boolean',
        'qr_logo_size' => 'integer',
    ];
}
