<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Seeds the admin account. The app is PUBLIC read-only — citizens browse with no
 * login — so the only account that ever authenticates is an admin (run detectors,
 * manage). `is_admin` is set by EXPLICIT property assignment (never mass
 * assignment) — the only path allowed to grant admin (security.md §5).
 *
 * (A non-admin authenticated user is only needed in tests, to prove a logged-in
 * non-admin is rejected from admin endpoints — use User::factory() there.)
 */
class AdminUserSeeder extends Seeder
{
    private const ADMIN_EMAIL = 'bobinkata@test.com';

    private const ADMIN_PASSWORD = 'BurgasPunk2026!';

    public function run(): void
    {
        // DEV ONLY. This seeder carries a hardcoded password, so it must never
        // run in production (security.md §7). In prod, mint the admin with the
        // env/prompt-driven `php artisan app:create-admin` instead.
        if (app()->environment('production')) {
            $this->command?->warn('AdminUserSeeder skipped in production — use `php artisan app:create-admin`.');

            return;
        }

        // Pass the PLAIN password — the User model's `password => hashed` cast hashes it once.
        // (Hashing here too would double-hash it, so the plain password would never match — that
        // was the "Грешен имейл или парола" bug.)
        $admin = User::updateOrCreate(
            ['email' => self::ADMIN_EMAIL],
            ['name' => 'Bobinkata', 'password' => self::ADMIN_PASSWORD],
        );
        $admin->is_admin = true; // explicit grant — bypasses mass-assignment guard
        $admin->save();

        $this->command?->info('Seeded admin account:');
        $this->command?->table(
            ['Email', 'Password', 'Admin'],
            [[self::ADMIN_EMAIL, self::ADMIN_PASSWORD, 'yes']],
        );
    }
}
