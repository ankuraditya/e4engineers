<?php

return [
    'name' => env('APP_NAME', 'E4ENGINEERS'),
    'frontend_url' => env('FRONTEND_URL', 'http://localhost:4177'),
    'currency' => env('APP_CURRENCY', 'INR'),
    'rate_limits' => [
        'api_per_minute' => (int) env('API_RATE_LIMIT', 60),
        'downloads_per_minute' => (int) env('DOWNLOAD_RATE_LIMIT', 20),
    ],
    'inventory' => [
        'low_stock_threshold' => (int) env('INVENTORY_LOW_STOCK_THRESHOLD', 5),
    ],
];
