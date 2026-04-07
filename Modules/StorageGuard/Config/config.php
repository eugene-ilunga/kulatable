<?php

use Modules\StorageGuard\Entities\StorageGuardGlobalSetting;

$productName = 'tabletrack';

return [
    'name' => 'StorageGuard',
    'verification_required' => true,
    'envato_item_id' => 61435922,
    'parent_envato_id' => 55116396,
    'parent_min_version' => '1.2.61',
    'script_name' => $productName . '-storage-guard',
    'parent_product_name' => $productName,
    'setting' => StorageGuardGlobalSetting::class,

];
