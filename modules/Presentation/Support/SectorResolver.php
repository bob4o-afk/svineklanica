<?php

declare(strict_types=1);

namespace Modules\Presentation\Support;

/**
 * Maps a CPV code to a citizen-facing procurement SECTOR — the same buckets the
 * frontend exposes as `ProcurementSector` (apps/web `lib/sectors.ts`). CPV is the
 * reliable category key (data-sources.md §3); this is the backend mirror of the
 * frontend's `sectorFromCpv`, kept byte-for-byte in sync (order matters: the more
 * specific road/water prefixes are checked before the generic construction `45`).
 */
final class SectorResolver
{
    /**
     * The full set of citizen-facing sectors (mirrors the frontend `ProcurementSector`).
     * Every value `fromCpv()` can return is in here — it's also the allow-list the feed
     * filter validates incoming `category` values against (security.md §5).
     *
     * @var string[]
     */
    public const SECTORS = ['roads', 'health', 'education', 'it', 'utilities', 'construction', 'supplies', 'other'];

    public static function isValid(string $sector): bool
    {
        return in_array($sector, self::SECTORS, true);
    }

    public static function fromCpv(?string $cpv): string
    {
        $code = preg_replace('/\D/', '', $cpv ?? '') ?? '';
        if ($code === '') {
            return 'other';
        }

        if (str_starts_with($code, '45233') || str_starts_with($code, '342') || str_starts_with($code, '60')) {
            return 'roads';
        }
        if (str_starts_with($code, '33') || str_starts_with($code, '85')) {
            return 'health';
        }
        if (str_starts_with($code, '80')) {
            return 'education';
        }
        if (str_starts_with($code, '30') || str_starts_with($code, '48') || str_starts_with($code, '72') || str_starts_with($code, '32')) {
            return 'it';
        }
        if (str_starts_with($code, '45231') || str_starts_with($code, '90') || str_starts_with($code, '65') || str_starts_with($code, '09')) {
            return 'utilities';
        }
        if (str_starts_with($code, '45')) {
            return 'construction';
        }
        if (str_starts_with($code, '15') || str_starts_with($code, '03')) {
            return 'supplies';
        }

        return 'other';
    }
}
