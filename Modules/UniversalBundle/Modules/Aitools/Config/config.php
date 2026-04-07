<?php

$addOnOf = 'tabletrack';

return [
    'name' => 'Aitools',
    'verification_required' => true,
    'envato_item_id' => 61538224,
    'parent_envato_id' => 55116396, // TableTrack Envato ID
    'parent_min_version' => '1.2.41',
    'script_name' => $addOnOf . '-aitools-module',
    'parent_product_name' => $addOnOf,
    'setting' => \Modules\Aitools\Entities\AiToolsGlobalSetting::class,
];
