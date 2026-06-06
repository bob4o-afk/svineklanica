<?php

use Laravel\Sanctum\Sanctum;

return [
    /*
     * Stateful domains use the httpOnly session cookie (SPA auth) instead of a
     * bearer token. Our web PWA origins go here. Everything else falls back to
     * token auth. localhost:5173 is the Vite dev server (apps/web).
     */
    'stateful' => explode(',', (string) env('SANCTUM_STATEFUL_DOMAINS', implode(',', [
        'localhost',
        'localhost:5173',
        '127.0.0.1',
        '127.0.0.1:8000',
        '::1',
        Sanctum::currentApplicationUrlWithPort(),
    ]))),

    'guard' => ['web'],

    // null = session lifetime (config/session.php). Set minutes to expire bearer tokens.
    'expiration' => null,

    'token_prefix' => env('SANCTUM_TOKEN_PREFIX', ''),

    'middleware' => [
        'authenticate_session' => Laravel\Sanctum\Http\Middleware\AuthenticateSession::class,
        'encrypt_cookies' => Illuminate\Cookie\Middleware\EncryptCookies::class,
        'validate_csrf_token' => Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
    ],
];
