<?php

declare(strict_types=1);

namespace Modules\Identity;

use Illuminate\Support\ServiceProvider;

/**
 * Identity bounded context: authentication (Sanctum), authorization policies,
 * and admin access. The User model stays in App\Models (Laravel auth + factory
 * expect it there); this module owns the auth LOGIC — controllers, DTOs,
 * policies, security middleware (security.md).
 */
class IdentityServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        //
    }
}
