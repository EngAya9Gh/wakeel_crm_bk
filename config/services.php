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

    'whatsapp' => [
        'base_url' => env('WHATSAPP_API_BASE_URL', 'https://provider.wakeel.cc/api/v1'),
        'api_key' => env('WHATSAPP_API_KEY', 'sk_c4e4f745ad1e073678e828a6c41def6fb7cb97ea5444346b6f501f92f9bc90ce'),
        'channel_id' => env('WHATSAPP_CHANNEL_ID', '15aa0381-e75d-4cc7-905b-863734b3e072'),
        'webhook_secret' => env('WHATSAPP_WEBHOOK_SECRET', 'wakeel_secret_2026'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Public API Keys
    |--------------------------------------------------------------------------
    |
    | API keys for public endpoints (e.g., website forms integration).
    | These keys are used to authenticate external services without user login.
    |
    */
    'api_keys' => [
        'public' => array_filter(explode(',', env('PUBLIC_API_KEYS', ''))),
    ],

];
