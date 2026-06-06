<?php

declare(strict_types=1);

namespace Modules\Identity\Security\Fingerprint;

use Illuminate\Http\Request;

/**
 * Extracts the INDEPENDENT identity signals from a request (security.md §3, §10)
 * so the blacklist can ban a caller across a VPN/IP change. Each signal persists
 * differently, so together they're hard to shed all at once:
 *
 *   - ip       — the network address (swapped by a VPN; weakest alone).
 *   - device   — our long-lived httpOnly cookie (survives ip change; lost on a
 *                cookie wipe; bots usually don't keep it).
 *   - client   — a localStorage id the frontend sends as X-Device-Id (survives
 *                a cookie wipe; the layer the user asked for).
 *   - clientfp — a JS canvas/WebGL fingerprint the frontend sends as X-Client-Fp
 *                (survives ip + cookie + localStorage wipe; strongest, browser-only).
 *   - headerfp — a hash of stable request headers (UA + languages + accept).
 *                Works even for headless scrapers that run no JS and keep no
 *                cookies — the one signal that bites a pure bot across ips.
 *
 * NB: a MAC address is deliberately NOT here — it is unobtainable from a web
 * request (link-layer only; stripped at the first router). We don't pretend.
 */
final class RequestFingerprint
{
    /** Name of the server-set persistent device cookie. */
    public const COOKIE = '__dvc';

    /** Header the frontend uses to echo its localStorage device id. */
    public const CLIENT_ID_HEADER = 'X-Device-Id';

    /** Header the frontend uses to send a JS browser fingerprint. */
    public const CLIENT_FP_HEADER = 'X-Client-Fp';

    /**
     * The signal map for this request, empty values dropped.
     *
     * @return array<string, string>
     */
    public static function signals(Request $request): array
    {
        return array_filter([
            'ip' => (string) ($request->ip() ?? ''),
            'device' => (string) $request->cookie(self::COOKIE, ''),
            'client' => substr((string) $request->header(self::CLIENT_ID_HEADER, ''), 0, 128),
            'clientfp' => substr((string) $request->header(self::CLIENT_FP_HEADER, ''), 0, 256),
            'headerfp' => self::headerFingerprint($request),
        ], static fn (string $v): bool => $v !== '');
    }

    /**
     * A stable digest of the headers a normal browser/scraper keeps constant
     * across requests. Coarse on purpose (it must not change between two requests
     * from the same client), so it complements — never replaces — the cookie and
     * localStorage signals.
     */
    public static function headerFingerprint(Request $request): string
    {
        $parts = [
            (string) $request->userAgent(),
            (string) $request->header('Accept-Language', ''),
            (string) $request->header('Accept', ''),
            (string) $request->header('Accept-Encoding', ''),
        ];

        // No identifying headers at all (often a crude bot) → no header signal.
        if (trim(implode('', $parts)) === '') {
            return '';
        }

        return hash('sha256', implode('|', $parts));
    }
}
