<?php

declare(strict_types=1);

namespace Modules\Identity\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Identity\Security\Blacklist\BlacklistService;
use Modules\Identity\Security\Fingerprint\RequestFingerprint;
use Modules\Identity\Security\Whitelist\WhitelistService;
use Symfony\Component\HttpFoundation\Response;

/**
 * The admin namespace (`/api/admin/*`) is for trusted operators ONLY (security.md §4).
 * A caller whose IP is NOT on the allow-list has no business here — reaching for the admin
 * login or console is treated as an intrusion attempt, so we auto-blacklist every signal the
 * request carried (like the honeypot, §10) and answer 404 so the surface is never confirmed.
 *
 * This guards ONLY the admin namespace — public endpoints (the citizen feed, map, stats) are
 * never whitelist-gated; the allow-list's other job is simply to bypass the abuse perimeter
 * (BlacklistMiddleware). It is OPT-IN: with no `SECURITY_IP_WHITELIST` configured there is no
 * allow-list to enforce, so the gate stands down and the admin routes fall back to their normal
 * `auth:sanctum` + `admin` guards (a fresh/dev install is never bricked).
 */
final class AdminWhitelistMiddleware
{
    public function __construct(
        private readonly WhitelistService $whitelist,
        private readonly BlacklistService $blacklist,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        // Only the admin namespace is gated. Public + non-admin routes pass straight through,
        // so this is safe to register on the whole `api` group.
        if (! $request->is('api/admin', 'api/admin/*')) {
            return $next($request);
        }

        $allowList = (array) config('security.whitelist.ips', []);

        // Opt-in: no allow-list configured → nothing to enforce. Banning every caller would
        // brick the site; admin routes still require auth:sanctum + admin.
        if ($allowList === []) {
            return $next($request);
        }

        if ($this->whitelist->isWhitelisted($request->ip() ?? '')) {
            return $next($request);
        }

        // Non-whitelisted caller probing the admin surface → ban every signal (ip + device +
        // fingerprint), then 404 (don't reveal the admin surface exists). Whitelisted operators
        // already bypass the blacklist gate, so this never locks out a trusted IP.
        $this->blacklist->blockSignals(
            RequestFingerprint::signals($request),
            'admin-whitelist:/'.ltrim($request->path(), '/'),
            (int) config('security.blacklist.ttl', 86400),
        );

        abort(404);
    }
}
