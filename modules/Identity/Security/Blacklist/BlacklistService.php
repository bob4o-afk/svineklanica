<?php

declare(strict_types=1);

namespace Modules\Identity\Security\Blacklist;

use App\Support\Logging\LoggingService;
use Illuminate\Support\Facades\Cache;
use Modules\Identity\Security\Whitelist\WhitelistService;

/**
 * The abuse blacklist (security.md §3). Cache-backed (Redis in prod, array in
 * tests), so a ban is fast to check on every request and expires on its own —
 * no permanent lockout by accident (entries carry a TTL and are reviewable).
 *
 * MULTI-SIGNAL by design: we don't ban only an ip (trivially swapped with a
 * VPN). A caller is identified by several INDEPENDENT, persistent signals —
 * ip, a long-lived device cookie, a localStorage device id, a request-header
 * fingerprint (see RequestFingerprint). When we ban, we ban EVERY signal the
 * offending request carried; to get back in an attacker must change ALL of
 * them at once (new ip AND cleared cookies AND cleared localStorage AND a new
 * browser fingerprint). A hit on ANY signal is a ban.
 *
 * Every stored key is a salted HASH of the value, never the raw value
 * (security.md §9 — privacy): we record who is banned and why, not a plaintext
 * map of everyone's ip/device.
 */
final class BlacklistService
{
    private const PREFIX = 'security:blacklist:';

    /**
     * A cache key holding the set of currently-tracked blacklist keys, so the
     * otherwise-unenumerable salted-hash bans can be listed and flushed wholesale
     * (`security:flush-blacklist`). Stored in the SAME cache store as the bans
     * themselves (Redis in prod) and mutated under a lock so concurrent bans don't
     * lose entries. It may accumulate keys for already-expired bans — flush() skips
     * those — and is itself cleared by a flush.
     */
    private const INDEX = self::PREFIX.'__index';

    /** The signal type used by the legacy ip-only helpers. */
    private const IP = 'ip';

    private readonly LoggingService $log;

    public function __construct(
        private readonly WhitelistService $whitelist,
    ) {
        $this->log = new LoggingService('security');
    }

    // --- IP helpers (the original surface; ip is just one signal type) --------

    public function isBlacklisted(string $ip): bool
    {
        return $this->isSignalBlocked(self::IP, $ip);
    }

    public function add(string $ip, string $reason, ?int $ttl = null): void
    {
        $this->blockSignals([self::IP => $ip], $reason, $ttl);
    }

    public function remove(string $ip): void
    {
        Cache::forget($this->key(self::IP, $ip));
    }

    /** Why this ip was banned (null if it isn't). For the reviewable admin view. */
    public function reason(string $ip): ?string
    {
        $entry = Cache::get($this->key(self::IP, $ip));

        return is_array($entry) ? ($entry['reason'] ?? null) : null;
    }

    // --- Multi-signal surface -------------------------------------------------

    /**
     * Is ANY of these signals banned? One match = banned (the VPN-hop defence:
     * a new ip still trips the device/fingerprint signal).
     *
     * @param  array<string, string>  $signals  type => value (e.g. ['ip' => ..., 'device' => ...])
     */
    public function anyBlocked(array $signals): bool
    {
        foreach ($signals as $type => $value) {
            if ($value !== '' && $this->isSignalBlocked($type, $value)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Ban every supplied signal at once, so switching just one (the ip) doesn't
     * let the caller back in.
     *
     * @param  array<string, string>  $signals  type => value
     */
    public function blockSignals(array $signals, string $reason, ?int $ttl = null): void
    {
        $ttl ??= (int) config('security.blacklist.ttl', 86400);

        $newKeys = [];

        foreach ($signals as $type => $value) {
            if ($value === '') {
                continue;
            }

            // A whitelisted ip is a trusted operator (security.md §4): the allow-list
            // beats the blacklist, so we never even RECORD it as banned — not just
            // skip it at request time. (Other signals of the same offending request
            // are still banned; only the trusted ip is spared.)
            if ($type === self::IP && $this->whitelist->isWhitelisted($value)) {
                continue;
            }

            $key = $this->key($type, $value);

            // Already banned → don't reset the clock or re-log the same offender.
            if (Cache::has($key)) {
                continue;
            }

            Cache::put($key, [
                'type' => $type,
                'reason' => $reason,
                'banned_at' => now()->toIso8601String(),
            ], $ttl);

            $newKeys[] = $key;
        }

        $this->indexKeys($newKeys);

        // One ban event per offence, not one per signal (less log noise). Logs a
        // short hash per signal type, never the raw value (privacy, §9).
        $this->log->warning('blacklist.add', [
            'reason' => $reason,
            'ttl' => $ttl,
            'signals' => $this->loggableSignals($signals),
        ]);
    }

    /**
     * Clear EVERY blacklist entry (all signal types) in one shot — the
     * `security:flush-blacklist` command. Walks the tracked index, forgets each
     * still-live ban, then drops the index itself. Returns how many live bans were
     * removed (expired/already-gone keys are skipped, not counted). Whitelisted
     * operators were never recorded, so they're unaffected.
     */
    public function flush(): int
    {
        /** @var array<int, string> $keys */
        $keys = (array) Cache::get(self::INDEX, []);

        $removed = 0;
        foreach (array_unique($keys) as $key) {
            if (Cache::has($key)) {
                Cache::forget($key);
                $removed++;
            }
        }

        Cache::forget(self::INDEX);

        $this->log->warning('blacklist.flush', ['removed' => $removed]);

        return $removed;
    }

    /**
     * Record freshly-banned keys in the enumerable index, under a lock so two
     * concurrent bans don't clobber each other's additions (security.md §3).
     *
     * @param  array<int, string>  $keys
     */
    private function indexKeys(array $keys): void
    {
        if ($keys === []) {
            return;
        }

        Cache::lock(self::INDEX.':lock', 5)->block(3, function () use ($keys): void {
            /** @var array<int, string> $current */
            $current = (array) Cache::get(self::INDEX, []);
            Cache::forever(self::INDEX, array_values(array_unique([...$current, ...$keys])));
        });
    }

    private function isSignalBlocked(string $type, string $value): bool
    {
        return Cache::has($this->key($type, $value));
    }

    /**
     * A stable, salted hash of (type, value). Salted with the app key so the
     * cache dump alone can't be brute-forced back to an ip/device.
     */
    private function key(string $type, string $value): string
    {
        return self::PREFIX.$type.':'.hash('sha256', $type.'|'.$value.'|'.config('app.key'));
    }

    /**
     * Short, non-reversible digests for the security log.
     *
     * @param  array<string, string>  $signals
     * @return array<string, string>
     */
    private function loggableSignals(array $signals): array
    {
        $out = [];
        foreach ($signals as $type => $value) {
            if ($value !== '') {
                $out[$type] = substr(hash('sha256', $value), 0, 16);
            }
        }

        return $out;
    }
}
