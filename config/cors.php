<?php

declare(strict_types=1);

// CORS is locked down (security.md §4): only our own front-ends, never "*".
// Origins come from env (comma-separated), so prod/demo domains are configured,
// not hardcoded.

$origins = array_filter(array_map(
    'trim',
    explode(',', (string) env('CORS_ALLOWED_ORIGINS', 'https://localhost'))
));

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie', 'login', 'logout'],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    'allowed_origins' => $origins,

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['Accept', 'Authorization', 'Content-Type', 'X-Requested-With', 'X-XSRF-TOKEN'],

    'exposed_headers' => ['X-Request-ID'],

    'max_age' => 0,

    // Required for Sanctum cookie auth from the SPA.
    'supports_credentials' => true,
];
