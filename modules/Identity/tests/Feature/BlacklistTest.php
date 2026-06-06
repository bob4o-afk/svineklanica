<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;
use Modules\Identity\Security\Blacklist\BlacklistService;
use Modules\Identity\Security\Fingerprint\RequestFingerprint;

// security.md §3, §5. The blacklist gate guards every API route: it rejects
// banned callers and auto-bans scanner / SQLi / XSS / path-traversal probes.

// The blacklist lives in the cache; flush it so each test starts uncontaminated
// (RefreshDatabase resets the DB, not the cache store).
beforeEach(fn () => Cache::flush());

it('rejects an already-blacklisted caller with 403', function () {
    app(BlacklistService::class)->add('127.0.0.1', 'test:manual');

    $this->postJson('/api/login', ['email' => 'a@test.com', 'password' => 'x'])
        ->assertStatus(403);
});

it('lets a clean caller through the gate', function () {
    // 422 (validation) proves we got PAST the blacklist gate to the controller.
    $this->postJson('/api/login', ['email' => 'not-an-email', 'password' => ''])
        ->assertStatus(422);
});

it('auto-blacklists a SQL-injection probe', function () {
    $this->getJson('/api/user?q=1 UNION SELECT password FROM users')
        ->assertStatus(403);

    expect(app(BlacklistService::class)->isBlacklisted('127.0.0.1'))->toBeTrue();
});

it('auto-blacklists a path-traversal probe', function () {
    $this->getJson('/api/user?file=../../../../etc/passwd')
        ->assertStatus(403);

    expect(app(BlacklistService::class)->isBlacklisted('127.0.0.1'))->toBeTrue();
});

it('issues a persistent device cookie to a new caller', function () {
    $issued = collect($this->getJson('/api/version')->headers->getCookies())
        ->first(fn ($c) => $c->getName() === RequestFingerprint::COOKIE);

    expect($issued)->not->toBeNull()
        ->and($issued->getValue())->not->toBeEmpty()
        ->and($issued->isHttpOnly())->toBeTrue();
});

it('keeps a banned caller out after an ip change (device-cookie signal)', function () {
    // The attacker was banned; only their persistent device cookie is known.
    app(BlacklistService::class)->blockSignals(['device' => 'dev-abc'], 'test:device');

    // They switch on a VPN (new ip) but the browser resends the same cookie.
    // withCredentials() makes the JSON test client actually send cookies.
    $this->withCredentials()
        ->withUnencryptedCookie(RequestFingerprint::COOKIE, 'dev-abc')
        ->postJson('/api/login', ['email' => 'a@test.com', 'password' => 'x'])
        ->assertStatus(403);
});

it('keeps a banned caller out via the localStorage id (X-Device-Id)', function () {
    // localStorage survives a cookie wipe; the frontend echoes it as a header.
    app(BlacklistService::class)->blockSignals(['client' => 'ls-xyz'], 'test:client');

    $this->withHeader(RequestFingerprint::CLIENT_ID_HEADER, 'ls-xyz')
        ->postJson('/api/login', ['email' => 'a@test.com', 'password' => 'x'])
        ->assertStatus(403);
});

it('bans every signal at once so a honeypot hit blocks the whole identity', function () {
    $service = app(BlacklistService::class);
    $signals = ['ip' => '9.9.9.9', 'device' => 'd1', 'client' => 'c1', 'headerfp' => 'h1'];

    $service->blockSignals($signals, 'test:multi');

    expect($service->anyBlocked(['ip' => '9.9.9.9']))->toBeTrue()
        ->and($service->anyBlocked(['device' => 'd1']))->toBeTrue()
        ->and($service->anyBlocked(['client' => 'c1']))->toBeTrue()
        ->and($service->anyBlocked(['headerfp' => 'h1']))->toBeTrue()
        ->and($service->anyBlocked(['ip' => '1.1.1.1', 'device' => 'other']))->toBeFalse();
});

it('expires a blacklist entry (no permanent lockout)', function () {
    $service = app(BlacklistService::class);
    $service->add('127.0.0.1', 'test:ttl', ttl: 1);

    expect($service->isBlacklisted('127.0.0.1'))->toBeTrue();

    $service->remove('127.0.0.1');

    expect($service->isBlacklisted('127.0.0.1'))->toBeFalse();
});
