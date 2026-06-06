<?php

declare(strict_types=1);

namespace Modules\Identity\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

/**
 * `php artisan app:create-admin` — the PROD-safe way to mint an admin (the only
 * account that ever authenticates; the app is public read-only). Replaces the
 * dev `AdminUserSeeder`, which carries a hardcoded password and must never run
 * in production (security.md §7).
 *
 * Credentials come from options, then env (ADMIN_EMAIL / ADMIN_NAME /
 * ADMIN_PASSWORD), then an interactive prompt — so it works both in a TTY and
 * unattended on the VM. `is_admin` is granted by EXPLICIT property assignment,
 * never mass assignment (security.md §5); the password is hashed by the model's
 * `hashed` cast (bcrypt/argon via the framework hasher).
 */
final class CreateAdminCommand extends Command
{
    protected $signature = 'app:create-admin
        {--email= : Admin email (else ADMIN_EMAIL, else prompt)}
        {--name= : Display name (else ADMIN_NAME, else prompt)}
        {--password= : Plain password (else ADMIN_PASSWORD, else a hidden prompt)}';

    protected $description = 'Create or update the admin account (hashed password, explicit is_admin grant).';

    public function handle(): int
    {
        $email = $this->resolve('email', 'ADMIN_EMAIL', fn () => $this->ask('Admin email'));
        $name = $this->resolve('name', 'ADMIN_NAME', fn () => $this->ask('Display name', 'Admin'));
        $password = $this->resolve('password', 'ADMIN_PASSWORD', fn () => $this->secret('Password'));

        $validator = Validator::make(
            ['email' => $email, 'name' => $name, 'password' => $password],
            [
                'email' => ['required', 'email'],
                'name' => ['required', 'string', 'max:255'],
                'password' => ['required', 'string', 'min:8'],
            ],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        // updateOrCreate mass-assigns only name + password (both fillable). The
        // `hashed` cast hashes the plain password; is_admin is set separately.
        $admin = User::updateOrCreate(
            ['email' => $email],
            ['name' => $name, 'password' => $password],
        );

        $wasExisting = ! $admin->wasRecentlyCreated;

        $admin->is_admin = true; // explicit grant — bypasses the mass-assignment guard
        $admin->save();

        $this->info(sprintf(
            '%s admin %s (%s).',
            $wasExisting ? 'Updated' : 'Created',
            $admin->email,
            $admin->public_id,
        ));

        return self::SUCCESS;
    }

    /**
     * Option → env → interactive fallback, in that order. The fallback is only
     * invoked (i.e. a prompt only shown) when neither an option nor env supplies
     * the value, so the command stays non-interactive on the VM.
     */
    private function resolve(string $option, string $envKey, callable $fallback): string
    {
        $value = $this->option($option);
        if (is_string($value) && $value !== '') {
            return $value;
        }

        $fromEnv = env($envKey);
        if (is_string($fromEnv) && $fromEnv !== '') {
            return $fromEnv;
        }

        return (string) $fallback();
    }
}
