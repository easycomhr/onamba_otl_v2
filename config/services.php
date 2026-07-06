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
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'integration_api_key' => env('INTEGRATION_API_KEY', ''),

    'teams' => [
        'admin_webhook_url' => env('TEAMS_ADMIN_WEBHOOK_URL', ''),
    ],

    'zalo' => [
        'oa_access_token' => env('ZALO_OA_ACCESS_TOKEN', ''),
        'oa_id'           => env('ZALO_OA_ID', ''),
        'app_id'          => env('ZALO_APP_ID', ''),
        'app_secret'      => env('ZALO_APP_SECRET', ''),
        'notify_user_id'  => env('ZALO_NOTIFY_USER_ID', ''),
    ],

];
