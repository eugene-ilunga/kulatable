<?php

$addOnOf = 'tabletrack';

return [
    'name' => 'Whatsapp',
    'verification_required' => true,
    'envato_item_id' => 61798806, // To be set when module is published
    'parent_envato_id' => 55116396, // TableTrack Envato ID
    'parent_min_version' => '1.2.48',
    'script_name' => $addOnOf . '-whatsapp-module',
    'parent_product_name' => $addOnOf,
    'setting' => Modules\Whatsapp\Entities\WhatsappGlobalSetting::class,
    'api_base_url' => env('WHATSAPP_API_BASE_URL', 'https://graph.facebook.com/v18.0'),
    'api_timeout' => env('WHATSAPP_API_TIMEOUT', 30),
];
