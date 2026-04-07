<?php

$addOnOf = 'tabletrack';

return [
    'name' => 'Loyalty',
    'verification_required' => true,
    'envato_item_id' => 61809429,
    'parent_envato_id' => 55116396, // TableTrack Envato ID
    'parent_min_version' => '1.2.42',
    'script_name' => $addOnOf . '-loyalty-module',
    'parent_product_name' => $addOnOf,
    'setting' => \Modules\Loyalty\Entities\LoyaltyGlobalSetting::class,
];
