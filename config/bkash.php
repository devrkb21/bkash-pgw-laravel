<?php

declare(strict_types=1);

$rawEnvironment = strtolower((string) env('BKASH_ENV', 'sandbox'));
$environment = in_array($rawEnvironment, ['production', 'prod', 'pay', 'live'], true)
    ? 'production'
    : 'sandbox';

return [
    'environment' => $environment,

    'base_url' => $environment === 'production'
        ? env('BKASH_PRODUCTION_BASE_URL', 'https://tokenized.pay.bka.sh/v2/tokenized-checkout')
        : env('BKASH_SANDBOX_BASE_URL', 'https://tokenized.sandbox.bka.sh/v2/tokenized-checkout'),

    'app_key' => env('BKASH_APP_KEY', ''),
    'app_secret' => env('BKASH_APP_SECRET', ''),
    'username' => env('BKASH_USERNAME', ''),
    'password' => env('BKASH_PASSWORD', ''),

    'timeout' => (int) env('BKASH_TIMEOUT', 30),
    'cache' => (bool) env('BKASH_CACHE', true),

    'enable_routes' => (bool) env('BKASH_ENABLE_ROUTES', false),

    'cache_keys' => [
        'token' => env('BKASH_TOKEN_CACHE_KEY', 'bkash.token'),
    ],
];
