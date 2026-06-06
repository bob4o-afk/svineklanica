<?php

declare(strict_types=1);

namespace Modules\Identity;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Identity\Console\Commands\CreateAdminCommand;
use Modules\Identity\Events\HoneypotEvent;
use Modules\Identity\Http\Controllers\HoneypotController;
use Modules\Identity\Http\Middleware\HoneypotMiddleware;
use Modules\Identity\Listeners\LogHoneypotHitListener;

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
            ]);
        }

        Route::middleware('api')
            ->prefix('api')
            ->group(__DIR__.'/routes/api.php');

        Route::middleware('api')
            ->prefix('api')
            ->group(__DIR__.'/routes/admin.php');

        $this->registerHoneypotRoutes();

        // A honeypot hit logs to the `security` channel asynchronously (the
        // listener is queued — backend.md §3).
        Event::listen(HoneypotEvent::class, LogHoneypotHitListener::class);
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
