<?php

$addOnOf = 'tabletrack';

return [
    'name' => 'UniversalBundle',
    'verification_required' => true,
    'envato_item_id' => 60154227,
    'parent_envato_id' => 55116396, // TableTrack Envato ID
    'parent_min_version' => '1.2.47',
    'script_name' => $addOnOf . '-universalbundle-module',
    'parent_product_name' => $addOnOf,
    'setting' => \Modules\UniversalBundle\Entities\UniversalBundleSetting::class,
];
