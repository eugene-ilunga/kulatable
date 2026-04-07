<?php

$addOnOf = 'tabletrack';

return [
    'name' => 'RestApi',

    /*
    |--------------------------------------------------------------------------
    | Firebase Cloud Messaging (FCM) - Delivery partner push notifications
    |--------------------------------------------------------------------------
    | Set FIREBASE_CREDENTIALS in .env to path of service account JSON file
    | (e.g. storage_path('app/firebase-credentials.json')) or leave null to disable.
    */
    'firebase_credentials_path' => env('FIREBASE_CREDENTIALS', null),
    'firebase_project_id' => env('FIREBASE_PROJECT_ID', null),
    'verification_required' => true,
    'envato_item_id' => 61504968,
    'parent_envato_id' => 55116396, // TableTrack Envato ID
    'parent_min_version' => '1.2.1',
    'script_name' => $addOnOf . '-restapi-module',
    'parent_product_name' => $addOnOf,
    'setting' => \Modules\RestApi\Entities\RestApiGlobalSetting::class,
];
