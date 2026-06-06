<?php

declare(strict_types=1);

namespace Modules\Identity\Http\Middleware;

use App\Support\Logging\LoggingService;
use App\Support\PublicId\PublicIdGenerator;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Cookie;
use Modules\Identity\Security\Blacklist\BlacklistService;
use Modules\Identity\Security\Fingerprint\RequestFingerprint;
use Symfony\Component\HttpFoundation\Cookie as SymfonyCookie;
use Symfony\Component\HttpFoundation\Response;

/**
 * The early gate on the API (security.md §3, §5). It:
 *   1. issues a long-lived device cookie if the caller has none (one of the
 *      VPN-proof identity signals),
 *   2. rejects a caller with 403 if ANY of their signals (ip / device cookie /
 *      localStorage id / browser fingerprint / header fingerprint) is banned,
 *   3. auto-bans a caller whose request matches a scanner signature (SQLi / XSS
 *      / path-traversal / command-injection probes) — banning EVERY signal,
 *   4. runs a tarpit counter — a caller spraying many requests in a short window
 *      gets all their signals banned (a scanner walking our URLs).
 *
 * Multi-signal banning is the anti-VPN measure: switching only the ip still
 * trips the device/fingerprint signals. Sits on the `api` group so every API
 * route — public browse included — is guarded (security.md §1). Honeypot decoy
 * routes are deliberately NOT behind this, so a trapped attacker keeps receiving
 * fake data instead of a 403.
 */
final class BlacklistMiddleware
{
    private readonly LoggingService $log;

    public function __construct(private readonly BlacklistService $blacklist)
    {
        $this->log = new LoggingService('security');
    }

    public function handle(Request $request, Closure $next): Response
    {
        $existing = $request->cookie(RequestFingerprint::COOKIE);
        $isNewDevice = ! is_string($existing) || $existing === '';
        $deviceId = $isNewDevice ? PublicIdGenerator::generate() : $existing;

        // Every identity signal on this request. `device` is forced in because a
        // freshly-minted cookie isn't reflected by $request->cookie() yet.
        $signals = ['device' => $deviceId] + RequestFingerprint::signals($request);

        if ($this->blacklist->anyBlocked($signals)) {
            abort(403, __('security.blacklisted'));
        }

        if ($signature = $this->matchedScannerSignature($request)) {
            $this->blacklist->blockSignals($signals, 'scanner:'.$signature);
            $this->log->warning('scanner.signature', [
                'signature' => $signature,
                'path' => $request->path(),
            ]);
            abort(403, __('security.blacklisted'));
        }

        $this->trackForTarpit($request, $signals);

        $response = $next($request);

        // Issue the persistent device cookie to a first-time caller. Set DIRECTLY
        // on the response (not Cookie::queue, which only fires for web/stateful
        // requests) so it reaches plain API clients too.
        if ($isNewDevice) {
            $response->headers->setCookie($this->deviceCookie($deviceId, $request));
        }

        return $response;
    }

    /**
     * The persistent, httpOnly device cookie. Long-lived (5y) and httpOnly so a
     * cookie wipe is the only way to drop it — it survives a VPN/ip change. Pairs
     * with the frontend's localStorage id (X-Device-Id), which survives a cookie
     * wipe too. Non-secret tracking id, never an auth token (kept out of cookie
     * encryption in bootstrap/app.php).
     */
    private function deviceCookie(string $id, Request $request): SymfonyCookie
    {
        $fiveYears = 60 * 24 * 365 * 5;

        return Cookie::make(
            name: RequestFingerprint::COOKIE,
            value: $id,
            minutes: $fiveYears,
            secure: $request->isSecure(),
            httpOnly: true,
            sameSite: 'lax',
        );
    }

    /** The probe haystack: path + query string + raw request body. */
    private function matchedScannerSignature(Request $request): ?string
    {
        // URL-decode so an encoded payload (`UNION%20SELECT`, `..%2F`) still
        // matches the whitespace/slash-based signatures.
        $haystack = $request->path()
            .' '.urldecode((string) $request->getQueryString())
            .' '.$request->getContent();

        foreach ((array) config('security.scanner_signatures', []) as $pattern) {
            if (preg_match($pattern, $haystack) === 1) {
                return $pattern;
            }
        }

        return null;
    }

    /**
     * Lightweight tarpit (security.md §10): count requests per ip in a sliding
     * window; cross the threshold → ban every signal. Catches a rapid scanner
     * even when no single request carries a signature.
     *
     * @param array<string, string> $signals
     */
    private function trackForTarpit(Request $request, array $signals): void
    {
        if (! config('security.tarpit.enabled', true)) {
            return;
        }

        $window = (int) config('security.tarpit.window_seconds', 60);
        $threshold = (int) config('security.tarpit.threshold', 20);
        $ip = $request->ip() ?? 'unknown';
        $key = 'security:tarpit:'.hash('sha256', $ip);

        $hits = (int) Cache::get($key, 0) + 1;
        Cache::put($key, $hits, $window);

        if ($hits > $threshold) {
            $this->blacklist->blockSignals($signals, 'tarpit:rate');
            $this->log->warning('tarpit.blacklist', [
                'ip_hash' => substr(hash('sha256', $ip), 0, 16),
                'hits' => $hits,
                'window' => $window,
            ]);
            abort(403, __('security.blacklisted'));
        }
    }
}
