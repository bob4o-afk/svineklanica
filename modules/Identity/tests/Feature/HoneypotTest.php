<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Modules\Identity\Events\HoneypotEvent;
use Modules\Identity\Http\Controllers\HoneypotController;
use Modules\Identity\Http\Middleware\HoneypotMiddleware;
use Modules\Identity\Security\Blacklist\BlacklistService;

// security.md §10. The decoy routes are a trap: hitting one fingerprints +
// blacklists the caller, serves believable FAKE data, and fires HoneypotEvent.

// The blacklist lives in the cache; flush it so each test starts uncontaminated
// (RefreshDatabase resets the DB, not the cache store).
beforeEach(function () {
    Cache::flush();
    // 127.0.0.1 must be an untrusted caller here (the operator's .env may whitelist it),
    // otherwise the honeypot would correctly skip banning a "trusted" caller.
    config(['security.whitelist.ips' => []]);
});

it('serves believable fake data on a honeypot hit (never the real DB)', function () {
    $response = $this->getJson('/api/.env');

    $response->assertOk()
        ->assertJsonStructure(['data' => [['public_id', 'authority', 'winner', 'value_bgn']], 'meta'])
        // Every fabricated record is marked SANDBOX — proves it isn't real data.
        ->assertJsonPath('data.0.registry_number', fn ($n) => str_contains((string) $n, 'SANDBOX'));
});

it('blacklists the caller after a honeypot hit', function () {
    expect(app(BlacklistService::class)->isBlacklisted('127.0.0.1'))->toBeFalse();

    $this->get('/wp-login.php');

    expect(app(BlacklistService::class)->isBlacklisted('127.0.0.1'))->toBeTrue();
});

it('locks the trapped caller out of the real API (403)', function () {
    $this->get('/.git/config');                 // trip the trap → blacklisted

    $this->postJson('/api/admin/login', ['email' => 'a@test.com', 'password' => 'x'])
        ->assertStatus(403);                     // now banned on the real API
});

it('fires a HoneypotEvent for the security log', function () {
    Event::fake([HoneypotEvent::class]);

    $this->getJson('/api/admin');

    Event::assertDispatched(HoneypotEvent::class, fn (HoneypotEvent $e) => $e->route === '/api/admin');
});

it('reads as a dead end (404) when the honeypot is disabled', function () {
    config()->set('honeypot.enabled', false);

    $this->getJson('/api/.env')->assertNotFound();
});

it('still blacklists but withholds fake data when serving is off', function () {
    config()->set('honeypot.serve_fake_data', false);

    $this->getJson('/api/.env')->assertNotFound();                 // no fake payload
    expect(app(BlacklistService::class)->isBlacklisted('127.0.0.1'))->toBeTrue(); // trap still fired
});

it('traps the bare /api/login (the web client uses /api/admin/login)', function () {
    // The decoy list is env-driven (HONEYPOT_ROUTES); register /api/login here so the
    // test is deterministic regardless of the operator's local env. This mirrors exactly
    // how IdentityServiceProvider wires every decoy.
    Route::any('/api/login', HoneypotController::class)->middleware(HoneypotMiddleware::class);

    $this->postJson('/api/login', ['email' => 'a@test.com', 'password' => 'x']);

    expect(app(BlacklistService::class)->isBlacklisted('127.0.0.1'))->toBeTrue();
});

it('never traps or bans a whitelisted IP that hits a decoy', function () {
    config()->set('security.whitelist.ips', ['127.0.0.1']);

    // A whitelisted operator who fat-fingers a decoy is not an attacker: no ban,
    // no event, no tarpit — they just get the harmless decoy response.
    $this->getJson('/api/admin');

    expect(app(BlacklistService::class)->isBlacklisted('127.0.0.1'))->toBeFalse();
});
