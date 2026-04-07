<?php

$addOnOf = 'tabletrack';


return [
    'name' => 'MultiPOS',
    'verification_required' => true,
    'envato_item_id' => 60897430,
    'parent_envato_id' => 55116396, // TableTrack Envato ID
    'parent_min_version' => '1.2.56',
    'script_name' => $addOnOf . '-multipos-module',
    'parent_product_name' => $addOnOf,
    'setting' => \Modules\MultiPOS\Entities\MultiPOSGlobalSetting::class,

    /*
    |--------------------------------------------------------------------------
    | POS Machine Cookie Configuration
    |--------------------------------------------------------------------------
    |
    | These settings control how the POS machine registration cookie is
    | stored and managed.
    |
    */
    'cookie' => [
        'name' => 'pos_token',
        'days' => 1825, // 5 years (lifetime - maximum practical cookie lifetime)
        'lifetime' => true, // Set to true for lifetime cookie
        'secure' => true,
        'http_only' => true,
        'same_site' => 'Strict',
    ],

    /*
    |--------------------------------------------------------------------------
    | Machine Status Settings
    |--------------------------------------------------------------------------
    |
    | Define the different statuses a POS machine can have
    |
    */
    'status' => [
        'pending' => 'pending',
        'active' => 'active',
        'declined' => 'declined',
    ],

    /*
    |--------------------------------------------------------------------------
    | Machine Approval Settings
    |--------------------------------------------------------------------------
    |
    | Control how machine registrations are approved
    |
    */
    'approval' => [
        'required' => true, // Whether admin approval is required
        'auto_approve' => false, // Auto approve after registration
    ],

    /*
    |--------------------------------------------------------------------------
    | Machine Monitoring Settings
    |--------------------------------------------------------------------------
    |
    | Settings for monitoring machine health and activity
    |
    */
    'monitoring' => [
        'heartbeat_interval' => 30, // seconds
        'offline_threshold' => 300, // seconds (5 minutes)
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Settings
    |--------------------------------------------------------------------------
    |
    | Security-related configurations
    |
    */
    'security' => [
        'token_length' => 64,
        'public_id_length' => 26, // ULID length
        'require_https' => true,
    ],
];
