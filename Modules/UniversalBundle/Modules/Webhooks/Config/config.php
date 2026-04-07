<?php

$addOnOf = 'tabletrack';


return [
    'name' => 'Webhooks',
    'verification_required' => true,
    'envato_item_id' => 61537635, // Webhooks Envato ID
    'parent_envato_id' => 55116396, // TableTrack Envato ID
    'parent_min_version' => '1.2.61',
    'script_name' => $addOnOf . '-webhooks-module',
    'parent_product_name' => $addOnOf,
    'setting' => \Modules\Webhooks\Entities\WebhooksGlobalSetting::class,
];
