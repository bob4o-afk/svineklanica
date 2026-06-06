<?php

declare(strict_types=1);

use App\Models\User;

it('never lets is_admin be mass-assigned (security.md §5)', function () {
    // A malicious DTO/array trying to smuggle is_admin must be ignored.
    $user = User::create([
        'name' => 'Mallory',
        'email' => 'mallory@test.com',
        'password' => 'secret-pass',
        'is_admin' => true,
    ]);

    expect($user->fresh()->is_admin)->toBeFalse();
});

it('grants admin only via explicit assignment (factory/seeder)', function () {
    $admin = User::factory()->admin()->create();

    expect($admin->is_admin)->toBeTrue();
});

it('assigns a UUIDv7 public_id on create and routes on it', function () {
    $user = User::factory()->create();

    expect($user->public_id)->not->toBeEmpty()
        ->and($user->getRouteKeyName())->toBe('public_id')
        // UUIDv7: the version nibble (start of the 3rd group) is 7.
        ->and($user->public_id)->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[0-9a-f]{4}-[0-9a-f]{12}$/');
});
