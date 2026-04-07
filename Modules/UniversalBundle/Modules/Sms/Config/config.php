<?php

$addOnOf = 'tabletrack';

return [
    'name' => 'Sms',
    'verification_required' => true,
    'envato_item_id' => 60530813,
    'parent_envato_id' => 55116396,
    'parent_min_version' => '1.2.53',
    'script_name' => $addOnOf . '-sms-module',
    'parent_product_name' => $addOnOf,
    'setting' => \Modules\Sms\Entities\SmsGlobalSetting::class,
];
