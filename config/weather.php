<?php

return [
    'api_key' => env('OPENWEATHER_API_KEY'),
    'base_url' => env('OPENWEATHER_BASE_URL', 'https://api.openweathermap.org/data/2.5'),
    'timeout' => (int) env('OPENWEATHER_TIMEOUT', 5),
    'cache_minutes' => 10,
];
