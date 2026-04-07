<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'pusher' => [
        'instance_id' => env('PUSHER_INSTANCE_ID'),
        'beam_secret' => env('PUSHER_BEAM_SECRET'),
    ],

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'organization_id' => env('OPENAI_ORGANIZATION_ID'),
        'model' => env('OPENAI_MODEL', 'gpt-4o'),
    ],

    'freshpay' => [
        'allowed_ips' => env('FRESHPAY_ALLOWED_IPS', ''),
        'network_prefixes' => [
            'airtel' => array_filter(array_map('trim', explode(',', env('FRESHPAY_AIRTEL_PREFIXES', '097,098,099,090')))),
            'orange' => array_filter(array_map('trim', explode(',', env('FRESHPAY_ORANGE_PREFIXES', '089')))),
            'mpesa' => array_filter(array_map('trim', explode(',', env('FRESHPAY_MPESA_PREFIXES', '081,082,084,085')))),
        ],
    ],

];
