<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // Google Generative Language API (Gemini) — powers the vectorized DB + the
    // AI-mapped search box (CLAUDE.md §1.2/§3). The key is the SAME GOOGLE_API_KEY
    // the apps/ai analyzer uses; here the backend reads it from its own env.
    // Embedding dimensionality is pinned to config('vector.dimensions') in code so
    // stored document vectors and the live query vector always match.
    'google' => [
        'key' => env('GOOGLE_API_KEY'),
        'embedding_model' => env('GOOGLE_EMBEDDING_MODEL', 'gemini-embedding-001'),
        'chat_model' => env('GOOGLE_SEARCH_MODEL', env('GEMINI_MODEL', 'gemini-3.1-flash-lite')),
        // Let Gemini rewrite the raw query into a cleaner retrieval phrase before
        // embedding. OFF by default: it adds a second API round-trip per search and
        // the embedding model already captures intent. Turn on for fuzzier queries.
        'refine_search' => (bool) env('GOOGLE_SEARCH_REFINE', false),
        // Query embeddings are cached (Redis) so a repeated search skips the network
        // round-trip entirely. Seconds; 0 disables. Doc embeddings already live in DB.
        'embed_cache_ttl' => (int) env('GOOGLE_EMBED_CACHE_TTL', 86400),
        // A search must feel instant: cap the live query-embed call so a slow API
        // falls back to keyword search fast instead of hanging the box.
        'query_timeout' => (int) env('GOOGLE_QUERY_TIMEOUT', 8),
    ],

];
