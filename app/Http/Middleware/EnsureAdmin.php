<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gates admin-only API routes to users with `is_admin` (security.md §1).
 * Mirrors leha's EnsureSuperAdmin, but on our simple admin flag instead of
 * Spatie roles. Returns JSON 401/403 (API-first — never redirects).
 */
final class EnsureAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            abort(401, __('auth.unauthenticated'));
        }

        if (! $user->isAdmin()) {
            abort(403, __('auth.forbidden'));
        }

        return $next($request);
    }
}
