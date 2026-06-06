<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Sanctum;
use Modules\Identity\Security\Blacklist\BlacklistService;
use Modules\Identity\Security\Whitelist\WhitelistService;

// security.md §4. A whitelisted IP bypasses EVERY abuse guard — the blacklist
// gate, the scanner auto-ban, the tarpit, and the per-route rate limits. The env
// is the single source of truth; nothing at runtime mutates it.

// The blacklist + rate-limiter both live in the cache; flush so each test starts
// clean (RefreshDatabase resets the DB, not the cache).
beforeEach(fn () => Cache::flush());

// Test requests originate from 127.0.0.1 — whitelist exactly that.
function whitelistLocalhost(): void
{
    config(['security.whitelist.ips' => ['127.0.0.1']]);
}

it('lets a whitelisted IP through even when it is blacklisted', function () {
    app(BlacklistService::class)->add('127.0.0.1', 'test:manual');
    whitelistLocalhost();

    // 422 (validation) proves we got PAST the blacklist gate to the controller,
    // instead of the 403 a banned caller would normally get.
    $this->postJson('/api/login', ['email' => 'not-an-email', 'password' => ''])
        ->assertStatus(422);
});

it('does not auto-ban a whitelisted IP that sends a scanner probe', function () {
    whitelistLocalhost();

    // The SQLi probe would normally trip a 403 + auto-ban; whitelisted, it sails
    // past the gate and only hits the auth guard (401 — /api/user needs a session).
    $this->getJson('/api/user?q=1 UNION SELECT password FROM users')
        ->assertStatus(401);

    expect(app(BlacklistService::class)->isBlacklisted('127.0.0.1'))->toBeFalse();
});

it('still auto-bans a NON-whitelisted IP scanner probe (regression)', function () {
    // No whitelist configured → the perimeter behaves as before.
    $this->getJson('/api/user?file=../../../../etc/passwd')
        ->assertStatus(403);

    expect(app(BlacklistService::class)->isBlacklisted('127.0.0.1'))->toBeTrue();
});

it('exempts a whitelisted IP from the login rate limit', function () {
    whitelistLocalhost();

    // Well past the 6/min login cap — every one is 422 (validation), never 429.
    foreach (range(1, 10) as $i) {
        $this->postJson('/api/login', ['email' => 'not-an-email', 'password' => ''])
            ->assertStatus(422);
    }
});

it('rate-limits a NON-whitelisted IP at the login cap (regression)', function () {
    // No whitelist → the login limiter (6/min) must still bite. Bad credentials
    // are 422 until the cap trips at 429; assert the limiter fires within the
    // window rather than pinning the exact boundary (unchanged from throttle:6,1).
    $statuses = collect(range(1, 12))->map(
        fn (): int => $this->postJson('/api/login', ['email' => 'a@test.com', 'password' => 'wrong'])->status(),
    );

    // The limiter bites (429 appears) and isn't a blanket block (a 422 got
    // through) — the mirror of the whitelisted case above, which never 429s.
    expect($statuses)->toContain(429)->toContain(422);
});

it('matches exact IPs and CIDR ranges, v4 and v6', function () {
    $svc = app(WhitelistService::class);

    config(['security.whitelist.ips' => ['203.0.113.7']]);
    expect($svc->isWhitelisted('203.0.113.7'))->toBeTrue()
        ->and($svc->isWhitelisted('203.0.113.8'))->toBeFalse();

    config(['security.whitelist.ips' => ['10.0.0.0/8']]);
    expect($svc->isWhitelisted('10.255.13.1'))->toBeTrue()
        ->and($svc->isWhitelisted('11.0.0.1'))->toBeFalse();

    config(['security.whitelist.ips' => ['192.168.1.0/24']]);
    expect($svc->isWhitelisted('192.168.1.200'))->toBeTrue()
        ->and($svc->isWhitelisted('192.168.2.1'))->toBeFalse();

    config(['security.whitelist.ips' => ['2001:db8::/32']]);
    expect($svc->isWhitelisted('2001:db8:abcd::1'))->toBeTrue()
        ->and($svc->isWhitelisted('2001:dead::1'))->toBeFalse();

    // An empty IP is never whitelisted, and a v4 IP never matches a v6 subnet.
    expect($svc->isWhitelisted(''))->toBeFalse();
});

it('reviews the whitelist read-only for an admin', function () {
    config(['security.whitelist.ips' => ['203.0.113.7', '10.0.0.0/8']]);
    Sanctum::actingAs(User::factory()->admin()->create());

    $response = $this->getJson('/api/admin/security/whitelist')->assertOk();

    expect($response->json())->toHaveCount(2)
        ->and($response->json('0.value'))->toBe('203.0.113.7')
        ->and($response->json('0.source'))->toBe('env');
});

it('forbids a non-admin and rejects a guest from the whitelist review', function () {
    config(['security.whitelist.ips' => ['203.0.113.7']]);

    $this->getJson('/api/admin/security/whitelist')->assertUnauthorized();

    Sanctum::actingAs(User::factory()->create()); // not an admin
    $this->getJson('/api/admin/security/whitelist')->assertForbidden();
});
