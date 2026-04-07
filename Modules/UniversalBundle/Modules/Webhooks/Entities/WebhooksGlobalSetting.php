<?php

namespace Modules\Webhooks\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Global webhooks settings that are used by all tenants.
 * These are managed exclusively by Super Admin.
 */
class WebhooksGlobalSetting extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    const MODULE_NAME = 'webhooks';

    protected $table = 'webhooks_global_settings';
}
