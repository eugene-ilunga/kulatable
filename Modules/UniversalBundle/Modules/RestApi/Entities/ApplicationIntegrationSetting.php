<?php

namespace Modules\RestApi\Entities;

use Illuminate\Database\Eloquent\Model;

class ApplicationIntegrationSetting extends Model
{
    protected $table = 'application_integration_settings';

    protected $fillable = [
        'public_token',
        'generated_by',
        'generated_at',
    ];

    public static function instance(): self
    {
        return static::firstOrCreate([]);
    }
}

