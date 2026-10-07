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

    'stripe' => [
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
        'adaptive_pricing' => env('STRIPE_ADAPTIVE_PRICING', false),
        // Local browser QA only (ops/qa/fake-stripe.php); ignored outside the local and testing environments.
        'api_base' => env('STRIPE_API_BASE'),
    ],

    // Google Search Console, read only (App\Services\Search\SearchConsole): the service-account key file, base64-encoded,
    // and the property it was added to. Empty key = not connected; nothing is fetched.
    'gsc' => [
        'service_account' => env('GSC_SERVICE_ACCOUNT'),
        'property' => env('SITE_GSC_PROPERTY', 'sc-domain:studymedicineuknigeria.com'),
    ],

];
