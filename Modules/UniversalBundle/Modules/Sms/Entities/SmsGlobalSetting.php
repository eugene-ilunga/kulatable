<?php

namespace Modules\Sms\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SmsGlobalSetting extends BaseModel
{
    use HasFactory;

    protected $guarded = ['id'];

    const MODULE_NAME = 'sms';

    protected $table = 'sms_global_settings';

    protected $fillable = [
        'license_type',
        'purchase_code',
        'purchased_on',
        'supported_until',
        'notify_update',
        'vonage_api_key',
        'vonage_api_secret',
        'vonage_from_number',
        'vonage_status',
        'msg91_auth_key',
        'msg91_from',
        'msg91_status',
        'android_sms_gateway_status',
        'android_sms_gateway_base_url',
        'android_sms_gateway_username',
        'android_sms_gateway_password',
        'android_sms_gateway_owner',
        'phone_verification_status',
    ];

    protected $casts = [
        'notify_update' => 'boolean',
        'vonage_status' => 'boolean',
        'msg91_status' => 'boolean',
        'android_sms_gateway_status' => 'boolean',
        'phone_verification_status' => 'boolean',
        'purchased_on' => 'datetime',
        'supported_until' => 'datetime',
    ];
}
