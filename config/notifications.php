<?php

declare(strict_types=1);

// Notifications config. Error alerting is e-mail based (Resend) instead of an
// external SaaS like Sentry — unhandled exceptions are mailed to the admin,
// rate-limited so a spike can't flood the inbox. See bootstrap/app.php.
return [

    // Where runtime error alerts go — set ADMIN_ALERT_EMAIL in .env / .env.prod.
    // No hardcoded address (this is an OSS repo). Empty/null disables error e-mails.
    'alert_email' => env('ADMIN_ALERT_EMAIL'),

    // Cap on error e-mails per minute (the rest are still logged, just not mailed).
    'alert_max_per_minute' => (int) env('ADMIN_ALERT_MAX_PER_MINUTE', 5),

];
