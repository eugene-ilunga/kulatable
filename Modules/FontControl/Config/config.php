<?php

$product = 'tabletrack';

return [
    'name' => 'FontControl',
    'verification_required' => false,
    'envato_item_id' => 61435916,
    'parent_envato_id' => 55116396,
    'parent_min_version' => '1.2.61',
    'script_name' => $product . '-fontcontrol-module',
    'parent_product_name' => $product,
    'setting' => \Modules\FontControl\Entities\FontControlGlobalSetting::class,
];
