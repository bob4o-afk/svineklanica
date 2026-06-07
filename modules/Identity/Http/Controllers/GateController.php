<?php

declare(strict_types=1);

namespace Modules\Identity\Http\Controllers;

use Illuminate\Http\Response;

/**
 * Whole-site blacklist gate (`GET /api/_gate`, security.md §3).
 *
 * Caddy `forward_auth` calls this before serving the SPA shell to a page
 * navigation. The real work happens BEFORE we get here: the BlacklistMiddleware
 * on the `api` group rejects a banned caller with 403, so Caddy never reaches a
 * 2xx and returns 403 for the ENTIRE page — not just /api. A clean caller falls
 * through to here and gets 204, which Caddy reads as "allowed" and proceeds to
 * serve the app.
 *
 * Invokable controller (not a closure) so the route survives `route:cache` in
 * the prod build (cf. App\Http\Controllers\VersionController).
 */
final class GateController
{
    public function __invoke(): Response
    {
        return response()->noContent();
    }
}
