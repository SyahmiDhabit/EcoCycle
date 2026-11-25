<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
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

    'ecocycle' => [
        'admin_email' => env('ECOCYCLE_ADMIN_EMAIL', 'ssyahmidhabit@gmail.com'),
        'admin_username' => env('ECOCYCLE_ADMIN_USERNAME'),
        'admin_password' => env('ECOCYCLE_ADMIN_PASSWORD'),
        'admin_locale' => env('ECOCYCLE_ADMIN_LOCALE', 'en'),
    ],

    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
        'model' => env('GEMINI_MODEL', 'gemini-3.1-flash-lite'),
        'photo_model' => env('GEMINI_PHOTO_MODEL', 'gemini-3.5-flash-lite'),
        'photo_fallback_model' => env('GEMINI_PHOTO_FALLBACK_MODEL', 'gemini-3.1-flash-lite'),
    ],

    'google_maps' => [
        'api_key' => env('GOOGLE_MAPS_API_KEY'),
    ],

    'stripe' => [
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
        'currency' => strtolower((string) env('STRIPE_CURRENCY', 'myr')),
        'api_base' => env('STRIPE_API_BASE', 'https://api.stripe.com/v1'),
        'tax_rate' => env('STRIPE_TAX_RATE'),
        'tax_label' => env('STRIPE_TAX_LABEL'),
    ],

];
