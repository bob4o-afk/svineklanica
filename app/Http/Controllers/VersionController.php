<?php

declare(strict_types=1);

namespace App\Http\Controllers;

/**
 * Public meta endpoint (`GET /api/version`). An invokable controller rather than
 * a closure so the route is cacheable — `php artisan route:cache` (run in the
 * prod build) refuses to serialize closure routes. Still passes the `api` group
 * guards (blacklist + throttle) like every other route (security.md §1).
 */
final class VersionController extends Controller
{
    /** @return array<string, string> */
    public function __invoke(): array
    {
        return [
            'name' => (string) config('app.name'),
            'api' => 'v1',
        ];
    }
}
