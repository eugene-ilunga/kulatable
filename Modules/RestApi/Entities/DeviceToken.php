<?php

namespace Modules\RestApi\Entities;

use Illuminate\Database\Eloquent\Model;

class DeviceToken extends Model
{
    protected $table = 'partner_device_token';

    protected $guarded = ['id'];

    /**
     * partner_device_token uses registration_id (ensured by migrations).
     */
    public static function tokenColumn(): string
    {
        return 'registration_id';
    }

}

