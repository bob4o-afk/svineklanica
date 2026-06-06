<?php

declare(strict_types=1);

use App\Http\Middleware\EnsureAdmin;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\RateLimiter;
use Modules\Identity\Http\Middleware\BlacklistMiddleware;
use Modules\Identity\Security\Fingerprint\RequestFingerprint;
use Modules\Notifications\Actions\SendNotificationAction;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/_health',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Behind Caddy (prod) the app sits behind a TLS-terminating proxy; honor
        // X-Forwarded-* so URLs are https and Secure cookies are set (devops.md §6).
        $middleware->trustProxies(at: '*');

        // Sanctum SPA auth: the web PWA authenticates via an httpOnly session
        // cookie (XSS-safe), not a JS-readable bearer token. Native clients could
        // still use bearer tokens via the same auth:sanctum guard.
        $middleware->statefulApi();

        // Security headers on every response (security.md §8): nosniff, frame
        // denial, CSP, referrer policy, and HSTS over HTTPS.
        $middleware->append(SecurityHeaders::class);

        // Abuse perimeter (security.md §3): every API route — public browse
        // included — passes the blacklist gate first. It rejects banned callers
        // and auto-bans scanner/SQLi/XSS probes before any work is done.
        // Prepended so it runs before throttling/auth. Honeypot decoy routes are
        // registered OUTSIDE this group (IdentityServiceProvider), so a trapped
        // attacker still gets fake data, not a 403.
        $middleware->prependToGroup('api', BlacklistMiddleware::class);

        // The persistent device-id cookie is a non-secret tracking signal, not an
        // auth token. Keep it OUT of cookie encryption so the blacklist gate reads
        // a stable plaintext value regardless of middleware order / stateful mode.
        $middleware->encryptCookies(except: [RequestFingerprint::COOKIE]);

        $middleware->alias([
            'admin' => EnsureAdmin::class,
            'blacklist' => BlacklistMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Error alerting via e-mail (Resend) instead of Sentry. Real server-side
        // failures are mailed to the admin as a QUEUED notification (backend.md §3),
        // rate-limited so a spike can't flood the inbox. Client errors (4xx) and
        // validation are skipped — they're not incidents. Logging is untouched.
        $exceptions->report(function (Throwable $e): void {
            if ($e instanceof HttpExceptionInterface && $e->getStatusCode() < 500) {
                return;
            }

            $to = (string) config('notifications.alert_email');
            if ($to === '') {
                return;
            }

            $perMinute = (int) config('notifications.alert_max_per_minute', 5);

            RateLimiter::attempt('error-alert-mail', $perMinute, function () use ($to, $e): void {
                app(SendNotificationAction::class)->execute(
                    $to,
                    'LiberHack alert: '.class_basename($e),
                    [
                        $e->getMessage(),
                        $e->getFile().':'.$e->getLine(),
                        'env: '.app()->environment(),
                    ],
                );
            });
        });
    })->create();
