<?php

$addOnOf = 'tabletrack';

return [
    'name' => 'Hotel',
    'verification_required' => true,
    'envato_item_id' => 62406221,
    'parent_envato_id' => 55116396, // TableTrack Envato ID
    'parent_min_version' => '1.0.0',
    'script_name' => $addOnOf . '-hotel-module',
    'parent_product_name' => $addOnOf,
    'setting' => \Modules\Hotel\Entities\HotelGlobalSetting::class,
];
