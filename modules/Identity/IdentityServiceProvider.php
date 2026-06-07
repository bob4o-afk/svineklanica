<?php

declare(strict_types=1);

namespace Modules\Identity;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Identity\Console\Commands\CreateAdminCommand;
use Modules\Identity\Console\Commands\FlushBlacklistCommand;
use Modules\Identity\Events\HoneypotEvent;
use Modules\Identity\Http\Controllers\HoneypotController;
use Modules\Identity\Http\Middleware\HoneypotMiddleware;
use Modules\Identity\Listeners\LogHoneypotHitListener;
use Modules\Identity\Security\Whitelist\WhitelistService;

/**
 * Identity bounded context: authentication (Sanctum stateful), authorization,
 * admin access, and the security perimeter — abuse blacklist + honeypot
 * deception (security.md §3, §10). The User model stays in App\Models (Laravel
 * auth + factory expect it there); this module owns the auth/security LOGIC —
 * controllers, DTOs, the EnsureAdmin gate, BlacklistMiddleware, honeypot.
 */
class IdentityServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                CreateAdminCommand::class,
                FlushBlacklistCommand::class,
            ]);
        }

        $this->registerRateLimiters();

        Route::middleware('api')
            ->prefix('api')
            ->group(__DIR__.'/routes/api.php');

        $this->registerHoneypotRoutes();

        // A honeypot hit logs to the `security` channel asynchronously (the
        // listener is queued — backend.md §3).
        Event::listen(HoneypotEvent::class, LogHoneypotHitListener::class);
    }

    /**
     * Named rate limiters (security.md §2, §4). Routes reference these by name
     * (`throttle:login`, `throttle:public`, `throttle:contact`) instead of inline
     * numeric limits, so the WHITELIST can short-circuit them: a trusted IP gets
     * `Limit::none()` and is never throttled. Limits are keyed by user when
     * authenticated, by IP otherwise. The numeric caps mirror the previous inline
     * values, so behaviour is unchanged for everyone except whitelisted callers.
     */
    private function registerRateLimiters(): void
    {
        $whitelist = $this->app->make(WhitelistService::class);

        // Bucket PER ROUTE + per identity (user when authed, else ip) — the same
        // granularity Laravel's inline `throttle:N,1` used, so sibling routes that
        // share a limiter name (e.g. subscribe + unsubscribe under 'contact')
        // don't drain one shared bucket.
        $key = static function (Request $request): string {
            $who = (string) ($request->user()?->getAuthIdentifier() ?? $request->ip());
            $route = $request->route()?->getName() ?? $request->route()?->uri() ?? $request->path();

            return $route.'|'.$who;
        };

        $limiter = static fn (int $perMinute) => static function (Request $request) use ($whitelist, $key, $perMinute): Limit {
            if ($whitelist->isWhitelisted($request->ip() ?? '')) {
                return Limit::none();
            }

            return Limit::perMinute($perMinute)->by($key($request));
        };

        // Login: tight, brute-force defence (was throttle:6,1).
        RateLimiter::for('login', $limiter(6));

        // Public browse / read endpoints (was throttle:120,1).
        RateLimiter::for('public', $limiter(120));

        // Contact / notification triggers — expensive, tight (was throttle:5,1).
        RateLimiter::for('contact', $limiter(5));
    }

    /**
     * Decoy routes (security.md §10): classic scanner targets that no legitimate
     * client ever calls and that are NOT linked anywhere. Registered at the app
     * root (verbatim paths like `/wp-login.php`, `/.git/config`) and behind ONLY
     * the HoneypotMiddleware — deliberately NOT the `api` group, so a trapped
     * attacker keeps receiving believable fake data instead of a 403. The route
     * list is env-driven (HONEYPOT_ROUTES); the middleware 404s them when the
     * honeypot is disabled, so they read as dead ends.
     */
    private function registerHoneypotRoutes(): void
    {
        $routes = (array) config('honeypot.routes', []);

        if ($routes === []) {
            return;
        }

        Route::middleware(HoneypotMiddleware::class)->group(function () use ($routes): void {
            foreach ($routes as $path) {
                Route::any('/'.ltrim((string) $path, '/'), HoneypotController::class);
            }
        });
    }
}
