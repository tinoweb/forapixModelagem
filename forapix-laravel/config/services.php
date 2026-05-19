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

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'api_key'      => env('RESEND_API_KEY', ''),
        'from_address' => env('RESEND_FROM_ADDRESS', 'noreply@apostacasada.net'),
        'from_name'    => env('RESEND_FROM_NAME', 'ApostaCasada'),
    ],

    'veopag' => [
        'client_id'     => env('VEOPAG_CLIENT_ID', ''),
        'client_secret' => env('VEOPAG_CLIENT_SECRET', ''),
    ],

    'admin' => [
        'email' => env('ADMIN_EMAIL', 'admin@apostacasada.net'),
    ],

    'frontend' => [
        'url' => env('FRONTEND_URL', env('APP_URL', 'http://localhost:8000')),
    ],

];
