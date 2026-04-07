<?php

namespace Modules\StorageGuard\Entities;

use App\Models\BaseModel;

class StorageGuardGlobalSetting extends BaseModel
{
    protected $table = 'storage_guard_global_settings';
    protected $guarded = ['id'];
}
