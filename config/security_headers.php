<?php

declare(strict_types=1);

// Response security headers (security.md §8), applied by App\Http\Middleware\
// SecurityHeaders. Read from config (cache-safe) — never env() in the middleware,
// since config:cache makes env() return null at runtime.
return [
    // Restrictive default. This backend serves JSON + the odd error/welcome page;
    // the React PWA is a separate origin. Tune per environment via env.
    'csp' => env('CONTENT_SECURITY_POLICY', "default-src 'self'; frame-ancestors 'none'; base-uri 'self'; form-action 'self'"),

    // Sent only over HTTPS (so local http dev isn't pinned to https).
    'hsts' => env('HSTS_HEADER', 'max-age=31536000; includeSubDomains'),

    'referrer_policy' => env('REFERRER_POLICY', 'strict-origin-when-cross-origin'),
];
