<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use App\Support\PublicId\PublicIdGenerator;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /** The current password being used by the factory. */
    protected static ?string $password;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'public_id' => PublicIdGenerator::generate(),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'is_admin' => false,
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Mark the user as an administrator. Only factories/seeders may do this
     * (security.md §5) — `is_admin` is guarded against mass assignment.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_admin' => true,
        ]);
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes): array => [
            'email_verified_at' => null,
        ]);
    }
}
