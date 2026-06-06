<?php

declare(strict_types=1);

namespace Modules\Identity\Security\Whitelist;

/**
 * The IP allow-list (security.md §4). A whitelisted caller bypasses EVERY abuse
 * guard — the blacklist gate, the scanner-signature auto-ban, the tarpit, and
 * the per-route rate limits — so a trusted operator (our VM, an office IP, the
 * demo machine) is never collateral damage of the perimeter defences.
 *
 * The env (`SECURITY_IP_WHITELIST`) is the SINGLE SOURCE OF TRUTH: a
 * comma-separated list of IPs or CIDR ranges, fixed at deploy time. Nothing at
 * runtime — no admin action, no API — can add to or remove from it; changing the
 * allow-list means changing config and redeploying. That keeps the most powerful
 * bypass in the system auditable in version control / infra, never mutable by a
 * compromised session. The admin console can READ it (review what's trusted) but
 * never write it.
 *
 * A value is an exact IP or a CIDR range (v4/v6); matching covers both.
 */
final class WhitelistService
{
    /** True if this ip falls inside ANY configured IP/CIDR entry. */
    public function isWhitelisted(string $ip): bool
    {
        if ($ip === '') {
            return false;
        }

        foreach ($this->values() as $value) {
            if ($this->matches($ip, $value)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Every configured entry, for the read-only admin review.
     *
     * @return array<int, array{value: string, source: string}>
     */
    public function all(): array
    {
        return array_map(
            static fn (string $value): array => ['value' => $value, 'source' => 'env'],
            $this->values(),
        );
    }

    /**
     * The configured IP/CIDR strings (the env list — the only source).
     *
     * @return array<int, string>
     */
    private function values(): array
    {
        return array_values((array) config('security.whitelist.ips', []));
    }

    /** Exact-IP or CIDR-range match (IPv4 and IPv6). */
    private function matches(string $ip, string $value): bool
    {
        if (! str_contains($value, '/')) {
            return $ip === $value;
        }

        [$subnet, $bits] = explode('/', $value, 2);
        if (! ctype_digit($bits)) {
            return false;
        }

        $ipBin = @inet_pton($ip);
        $subnetBin = @inet_pton($subnet);
        // Mismatched families (v4 ip vs v6 subnet) or malformed input never match.
        if ($ipBin === false || $subnetBin === false || strlen($ipBin) !== strlen($subnetBin)) {
            return false;
        }

        $prefix = (int) $bits;
        if ($prefix < 0 || $prefix > strlen($ipBin) * 8) {
            return false;
        }

        $fullBytes = intdiv($prefix, 8);
        if ($fullBytes > 0 && substr($ipBin, 0, $fullBytes) !== substr($subnetBin, 0, $fullBytes)) {
            return false;
        }

        $remainingBits = $prefix % 8;
        if ($remainingBits === 0) {
            return true;
        }

        $mask = ~((1 << (8 - $remainingBits)) - 1) & 0xFF;

        return (ord($ipBin[$fullBytes]) & $mask) === (ord($subnetBin[$fullBytes]) & $mask);
    }
}
