<?php

declare(strict_types=1);

// Notifications config. Error alerting is e-mail based (Resend) instead of an
// external SaaS like Sentry — unhandled exceptions are mailed to the admin,
// rate-limited so a spike can't flood the inbox. See bootstrap/app.php.
return [

    // Where runtime error alerts go. Defaults to the project owner; override per
    // env. Empty/null disables error e-mails entirely.
    'alert_email' => env('ADMIN_ALERT_EMAIL', 'borislav.milanov@ux2.dev'),

    // Cap on error e-mails per minute (the rest are still logged, just not mailed).
    'alert_max_per_minute' => (int) env('ADMIN_ALERT_MAX_PER_MINUTE', 5),

];
