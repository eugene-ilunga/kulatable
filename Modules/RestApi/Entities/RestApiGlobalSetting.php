<?php

namespace Modules\RestApi\Entities;

use Illuminate\Database\Eloquent\Model;

class RestApiGlobalSetting extends Model
{
    protected $table = 'restapi_global_settings';

    const MODULE_NAME = 'RestApi';

    protected $guarded = ['id'];

    protected $casts = [
        'firebase_enabled' => 'boolean',
        'firebase_server_key' => 'string',
        'firebase_project_id' => 'string',
        'firebase_service_account_json' => 'string',
        'firebase_priority' => 'string',
        'firebase_sound' => 'string',
    ];

    public static function instance(): self
    {
        /** @var self|null $setting */
        $setting = static::query()->first();

        if (!$setting) {
            $setting = static::create([]);
        }

        return $setting;
    }

}
