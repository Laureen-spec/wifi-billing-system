<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Safe local mode
    |--------------------------------------------------------------------------
    | When true, SMS messages move queued -> sent without calling a real gateway.
    | Keep true on local/XAMPP. Set false only after configuring a real provider.
    */
    'dry_run' => env('ISP_SMS_DRY_RUN', true),

    'default_provider' => env('ISP_SMS_PROVIDER', 'platform'),

    'custom_http' => [
        'timeout' => env('ISP_SMS_HTTP_TIMEOUT', 20),
    ],
];
