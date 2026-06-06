<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sets response security headers on every request (security.md §8): nosniff,
 * frame denial / CSP frame-ancestors, referrer policy, and HSTS over HTTPS.
 * Values come from config/security_headers.php (cache-safe).
 */
final class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $headers = $response->headers;
        $headers->set('X-Content-Type-Options', 'nosniff');
        $headers->set('X-Frame-Options', 'DENY');
        $headers->set('X-Permitted-Cross-Domain-Policies', 'none');
        $headers->set('Referrer-Policy', (string) config('security_headers.referrer_policy'));

        $csp = (string) config('security_headers.csp');
        if ($csp !== '') {
            $headers->set('Content-Security-Policy', $csp);
        }

        // HSTS only over HTTPS — don't pin http dev to https.
        $hsts = (string) config('security_headers.hsts');
        if ($hsts !== '' && $request->secure()) {
            $headers->set('Strict-Transport-Security', $hsts);
        }

        return $response;
    }
}
