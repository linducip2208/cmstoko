<?php

return [
    'name' => env('APP_NAME', 'TokoKita'),
    'tagline' => 'Belanja mudah, kualitas premium.',
    'origin_city_id' => env('RAJAONGKIR_ORIGIN_CITY', 152),
    'couriers' => ['jne', 'pos', 'tiki'],
    'flat_shipping_cost' => 20000,
    'flat_shipping_service' => 'Reguler',
    'flat_shipping_etd' => '2-4',

    // Return request window (days after order creation). Content policy is CMS-configurable.
    'return_window_days' => env('SHOP_RETURN_WINDOW_DAYS', 7),

    'midtrans' => [
        'server_key' => env('MIDTRANS_SERVER_KEY'),
        'client_key' => env('MIDTRANS_CLIENT_KEY'),
        'is_production' => env('MIDTRANS_IS_PRODUCTION', false),
    ],

    'rajaongkir' => [
        'api_key' => env('RAJAONGKIR_API_KEY'),
        'base_url' => env('RAJAONGKIR_BASE_URL', 'https://api.rajaongkir.com/starter'),
    ],

    'bank_accounts' => [
        ['bank' => 'BCA', 'number' => '1234567890', 'holder' => 'PT TokoKita Sejahtera'],
        ['bank' => 'Mandiri', 'number' => '9876543210', 'holder' => 'PT TokoKita Sejahtera'],
    ],
];
