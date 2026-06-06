<?php

declare(strict_types=1);

namespace Modules\Identity\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Identity\Security\Honeypot\FakeDataSandbox;

/**
 * Serves the decoy response after HoneypotMiddleware has already fingerprinted +
 * blacklisted the caller (security.md §10). Thin: it asks the sandbox for
 * believable FAKE data and returns it dressed up like a real API list endpoint.
 * If fake-data serving is off, it just 404s (the trap still fired in middleware).
 */
final class HoneypotController
{
    public function __invoke(Request $request, FakeDataSandbox $sandbox): JsonResponse
    {
        if (! config('honeypot.serve_fake_data', true)) {
            abort(404);
        }

        // 200 + believable headers so a scraper treats it as a live endpoint.
        return response()->json($sandbox->payloadFor($request))
            ->header('X-Powered-By', 'PHP/8.3')
            ->header('Cache-Control', 'no-store');
    }
}
