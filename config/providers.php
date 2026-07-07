<?php

return [
    'placas' => [
        'url' => env('PLACAS_API_URL', 'https://placas.info/api/v2/consultar/'),
        'token' => env('PLACAS_API_TOKEN', ''),
        'callback_url' => env('PLACAS_CALLBACK_URL', ''),
        'callback_secret' => env('PLACAS_CALLBACK_SECRET', ''),
        'placa_min' => (int) env('PLACAS_PLACA_MIN', 5),
        'placa_max' => (int) env('PLACAS_PLACA_MAX', 8),
        'niv_length' => (int) env('PLACAS_NIV_LENGTH', 17),
        'http_timeout' => (int) env('PLACAS_HTTP_TIMEOUT', 30),
        'poll_max_seconds' => (int) env('PLACAS_POLL_MAX_SECONDS', 40),
        'poll_interval_seconds' => (int) env('PLACAS_POLL_INTERVAL_SECONDS', 2),
    ],
];
