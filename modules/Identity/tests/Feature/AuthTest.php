<?php

declare(strict_types=1);

use App\Models\User;
use Laravel\Sanctum\Sanctum;

// The SPA sends an Origin from a stateful domain, so Sanctum attaches the
// session middleware (cookie auth). Simulate that for the login flow.
beforeEach(function () {
    $this->withHeader('Origin', 'http://localhost');
});

it('lets an admin log in and returns their public-safe user', function () {
    User::factory()->admin()->create([
        'email' => 'admin@test.com',
        'password' => 'secret-pass-1',
    ]);

    $response = $this->postJson('/api/login', [
        'email' => 'admin@test.com',
        'password' => 'secret-pass-1',
    ]);

    $response->assertOk()
        ->assertJsonPath('isAdmin', true)
        ->assertJsonPath('email', 'admin@test.com')
        ->assertJsonMissingPath('id'); // never leak the internal id
    expect($response->json('publicId'))->not->toBeEmpty();
});

it('rejects invalid credentials', function () {
    User::factory()->create(['email' => 'u@test.com', 'password' => 'right-pass']);

    $this->postJson('/api/login', ['email' => 'u@test.com', 'password' => 'wrong'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('email');
});

it('throttles brute-force login attempts', function () {
    for ($i = 0; $i < 6; $i++) {
        $this->postJson('/api/login', ['email' => 'x@test.com', 'password' => 'nope']);
    }

    $this->postJson('/api/login', ['email' => 'x@test.com', 'password' => 'nope'])
        ->assertStatus(429);
});

it('returns the authenticated user from /api/user', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->getJson('/api/user')
        ->assertOk()
        ->assertJsonPath('publicId', $user->public_id);
});

it('rejects unauthenticated access to /api/user', function () {
    $this->getJson('/api/user')->assertUnauthorized();
});
