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

    'swish_payout' => [
        'base_url'    => env('SWISH_PAYOUT_BASE_URL'),
        'client_cert' => env('SWISH_PAYOUT_CLIENT_CERT'),
        'client_key'  => env('SWISH_PAYOUT_CLIENT_KEY'),
        'client_key_password' => env('SWISH_PAYOUT_CLIENT_KEY_PASSWORD'),
        'client_cert_password' => env('SWISH_PAYOUT_CLIENT_CERT_PASSWORD'),
        'ca_cert'     => env('SWISH_PAYOUT_CA_CERT'),
        'callback_url'=> env('SWISH_PAYOUT_CALLBACK_URL'),
        'payer_alias' => env('SWISH_PAYOUT_PAYER_ALIAS'),
        'signing_cert' => env('SWISH_PAYOUT_SIGNING_CERT'),
        'signing_key'  => env('SWISH_PAYOUT_SIGNING_KEY'),
        'signing_key_password' => env('SWISH_PAYOUT_SIGNING_KEY_PASSWORD', 'swish'),
        'use_callback_identifier' => env('SWISH_PAYOUT_USE_CALLBACK_IDENTIFIER', false),
    ],

];
