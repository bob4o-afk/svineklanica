<?php

declare(strict_types=1);

// Abuse / blacklist config (security.md §3, §5). The blacklist rejects known-bad
// callers early with 403; the scanner signatures auto-blacklist a caller that
// sends SQLi/XSS/path-traversal/scanner payloads. Keyed by a HASHED ip (§9 —
// never store raw ips as plaintext keys).
return [

    'blacklist' => [
        // Default TTL (seconds) for an auto-blacklist entry. Reviewable, expiring —
        // no permanent lockout by accident (security.md §3). Default 24h.
        'ttl' => (int) env('BLACKLIST_TTL', 86400),
    ],

    // Tarpit: after this many requests from one ip inside the window, the caller
    // is auto-blacklisted (a scanner spraying URLs). The threshold must sit WELL
    // above real usage — a citizen opening the app fires several API calls per
    // page, so a low cap would ban legitimate users. A scanner does hundreds to
    // thousands/min; a heavy human peaks ~60–120/min. 300/min leaves headroom.
    'tarpit' => [
        'enabled' => env('TARPIT_ENABLED', true),
        'threshold' => (int) env('TARPIT_THRESHOLD', 300),
        'window_seconds' => (int) env('TARPIT_WINDOW', 60),
    ],

    // Scanner signatures: a request whose path/query/body matches any of these is
    // a near-certain attack probe → auto-blacklist + 403. Case-insensitive regex.
    'scanner_signatures' => [
        // SQL injection
        '/\bunion\b\s+\bselect\b/i',
        '/\bselect\b.+\bfrom\b/i',
        '/\bor\b\s+1\s*=\s*1/i',
        '/(\%27)|(\')\s*(or|and)\s/i',
        '/\b(sleep|benchmark|pg_sleep)\s*\(/i',
        // XSS
        '/<script\b/i',
        '/javascript:/i',
        '/\bon(error|load|click)\s*=/i',
        // Path traversal / local file inclusion
        '/\.\.[\/\\\\]/',
        '/\/etc\/passwd/i',
        '/php:\/\//i',
        // Command injection
        '/;\s*(cat|wget|curl|nc|bash|sh)\b/i',
    ],

];
