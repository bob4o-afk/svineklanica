<?php

declare(strict_types=1);

namespace Modules\Identity\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Identity\Events\HoneypotEvent;
use Modules\Identity\Security\Blacklist\BlacklistService;
use Modules\Identity\Security\Fingerprint\RequestFingerprint;
use Modules\Identity\Security\Whitelist\WhitelistService;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guards the decoy routes (security.md §10). A hit here is a near-certain
 * bot/scanner/attacker signal, so we:
 *   1. fingerprint the caller (ip, UA, headers),
 *   2. auto-add them to the blacklist (§3),
 *   3. fire HoneypotEvent (queued listener logs to the `security` channel),
 *   4. optionally tarpit (slow the response) to waste their time,
 * then hand off to HoneypotController which serves believable FAKE data.
 *
 * When the honeypot is disabled the decoy routes look like dead ends (404) — no
 * trap fires.
 */
final class HoneypotMiddleware
{
    public function __construct(
        private readonly BlacklistService $blacklist,
        private readonly WhitelistService $whitelist,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! config('honeypot.enabled', true)) {
            abort(404);
        }

        $route = '/'.ltrim($request->path(), '/');

        // A whitelisted operator hitting a decoy is NOT an attacker — never ban,
        // log, or tarpit them (security.md §4: the allow-list bypasses every
        // perimeter guard). They just get the same harmless decoy response.
        if (! $this->whitelist->isWhitelisted($request->ip() ?? '')) {
            // Ban EVERY signal the request carried (ip + device + localStorage id +
            // browser/header fingerprint), not just the ip — so a VPN switch alone
            // won't get the attacker back onto the real API.
            $this->blacklist->blockSignals(
                RequestFingerprint::signals($request),
                'honeypot:'.$route,
                (int) config('honeypot.blacklist_ttl', 86400),
            );

            HoneypotEvent::dispatch(
                $this->fingerprint($request),
                $route,
                now()->toIso8601String(),
            );

            $this->tarpit();
        }

        return $next($request);
    }

    /**
     * The caller's observable signature — what we keep to study the attacker.
     * Defensive only: we record what THEY sent us, we never probe back.
     *
     * @return array<string, mixed>
     */
    private function fingerprint(Request $request): array
    {
        return [
            'ip' => $request->ip(),
            'method' => $request->method(),
            'user_agent' => substr((string) $request->userAgent(), 0, 512),
            'referer' => $request->headers->get('referer'),
            'accept' => $request->headers->get('accept'),
            'query' => $request->query(),
        ];
    }

    private function tarpit(): void
    {
        $seconds = (int) config('honeypot.tarpit_seconds', 0);

        // Never slow tests/CI — the tarpit is a prod-only time-waster.
        if ($seconds > 0 && ! app()->environment('testing')) {
            sleep($seconds);
        }
    }
}
