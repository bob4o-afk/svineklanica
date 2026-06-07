<?php

declare(strict_types=1);

// Honeypot / deception config (security.md §10). Decoy routes that no legitimate
// client ever calls and that are NOT linked anywhere (UI, sitemap, robots). A hit
// is a near-certain bot/scanner/attacker signal: we fingerprint + blacklist the
// caller, serve believable FAKE data from an isolated sandbox (NEVER the real DB)
// to waste their time, and log the full interaction to the `security` channel.
return [

    // Master switch. When false, the decoy routes still exist but respond 404 —
    // they look like dead ends, no trap fires.
    'enabled' => env('HONEYPOT_ENABLED', true),

    // When true, a honeypot hit returns believable fake procurement data (to keep
    // a scraper busy and let us watch). When false, it just blacklists + 404s.
    'serve_fake_data' => env('HONEYPOT_SERVE_FAKE_DATA', true),

    // Tarpit: deliberately slow the response to waste a scraper's time. Seconds.
    // 0 = off (default, so it never slows CI/tests). Prod can set e.g. 2.
    'tarpit_seconds' => (int) env('HONEYPOT_TARPIT_SECONDS', 0),

    // How long (seconds) a honeypot-triggered blacklist entry lasts. Reviewable,
    // never permanent (security.md §3). Default 24h.
    'blacklist_ttl' => (int) env('HONEYPOT_BLACKLIST_TTL', 86400),

    // The decoy paths. Comma-separated in env; these are classic scanner targets
    // (a bot walking common URLs trips them). Registered verbatim at the app root.
    'routes' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env(
            'HONEYPOT_ROUTES',
            // /api/login is a decoy: the web client logs in via /api/admin/login, so anyone
            // hitting the bare /api/login is a scanner. /api/admin is the bare admin probe.
            '/api/login,/api/admin,/api/.env,/api/internal/db-dump,/api/v1/users/export,/wp-login.php,/.git/config,/.env,/phpinfo.php'
        ))
    ))),

];
