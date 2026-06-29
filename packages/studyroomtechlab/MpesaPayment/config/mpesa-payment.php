<?php

return [

    /*
    |--------------------------------------------------------------------------
    | M-Pesa Environment
    |--------------------------------------------------------------------------
    |
    | sandbox = testing
    | live    = real payments
    |
    */

    'environment' => env('MPESA_ENVIRONMENT', 'sandbox'),

    /*
    |--------------------------------------------------------------------------
    | Daraja API Credentials
    |--------------------------------------------------------------------------
    */

    'consumer_key' => env('MPESA_CONSUMER_KEY'),
    'consumer_secret' => env('MPESA_CONSUMER_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Business Shortcode / Till / Paybill
    |--------------------------------------------------------------------------
    |
    | For STK Push, this can be Paybill or Till shortcode depending on setup.
    |
    */

    'shortcode' => env('MPESA_SHORTCODE'),
    'passkey' => env('MPESA_PASSKEY'),

    /*
    |--------------------------------------------------------------------------
    | Callback URL
    |--------------------------------------------------------------------------
    |
    | Safaricom sends payment result here.
    |
    */

    'callback_url' => env('MPESA_CALLBACK_URL'),

    /*
    |--------------------------------------------------------------------------
    | Default STK Details
    |--------------------------------------------------------------------------
    */

    'account_reference' => env('MPESA_ACCOUNT_REFERENCE', 'StudyRoom WiFi'),
    'transaction_desc' => env('MPESA_TRANSACTION_DESC', 'WiFi subscription payment'),

    /*
    |--------------------------------------------------------------------------
    | API URLs
    |--------------------------------------------------------------------------
    */

    'sandbox' => [
        'oauth_url' => 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials',
        'stk_push_url' => 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest',
        'stk_query_url' => 'https://sandbox.safaricom.co.ke/mpesa/stkpushquery/v1/query',
    ],

    'live' => [
        'oauth_url' => 'https://api.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials',
        'stk_push_url' => 'https://api.safaricom.co.ke/mpesa/stkpush/v1/processrequest',
        'stk_query_url' => 'https://api.safaricom.co.ke/mpesa/stkpushquery/v1/query',
    ],

    /*
    |--------------------------------------------------------------------------
    | Safety
    |--------------------------------------------------------------------------
    */

    'timeout_seconds' => env('MPESA_TIMEOUT_SECONDS', 30),
    'currency' => 'KES',
];