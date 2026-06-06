<?php

declare(strict_types=1);

namespace Modules\Identity\Http\Controllers\Admin;

use Modules\Identity\Data\WhitelistEntryData;
use Modules\Identity\Security\Whitelist\WhitelistService;

/**
 * Admin security console (security.md §4). For now: REVIEW the IP allow-list.
 * The env is the single source of truth — the whitelist is read-only here, so
 * there is no store/destroy; changing trusted IPs is a config + redeploy action,
 * never a runtime API call. Guarded by auth:sanctum + admin at the route.
 */
final class SecurityController
{
    public function __construct(private readonly WhitelistService $whitelist) {}

    /**
     * The full allow-list (read-only — env is the single source of truth).
     *
     * @return array<int, WhitelistEntryData>
     */
    public function whitelist(): array
    {
        return array_map(
            static fn (array $entry): WhitelistEntryData => WhitelistEntryData::fromArray($entry),
            $this->whitelist->all(),
        );
    }
}
