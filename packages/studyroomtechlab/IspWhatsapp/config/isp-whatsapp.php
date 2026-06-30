<?php

return [
    'dry_run' => env('ISP_WHATSAPP_DRY_RUN', true),
    'default_currency' => env('ISP_WHATSAPP_CURRENCY', 'KES'),
    'default_message_cost' => (float) env('ISP_WHATSAPP_MESSAGE_COST', 1),
    'custom_http' => [
        'timeout' => (int) env('ISP_WHATSAPP_HTTP_TIMEOUT', 20),
    ],
];
