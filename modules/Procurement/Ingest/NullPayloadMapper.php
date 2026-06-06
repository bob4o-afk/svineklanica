<?php

declare(strict_types=1);

namespace Modules\Procurement\Ingest;

use Carbon\CarbonInterface;

/**
 * Provenance-only mapper: the record is kept in `ingest_records` (the action
 * already wrote + marked it) but has no domain table yet. Used for canonical
 * record types we ingest uniformly but don't project into a typed table yet —
 * job, asset, donation, concession, declaration, audit, project, reference
 * (contract.py v2). Promoting one to a real table later = swap its registry
 * entry for a dedicated mapper; nothing in the scraper changes.
 */
final class NullPayloadMapper implements PayloadMapper
{
    public function recordType(): string
    {
        return '_provenance_only';
    }

    public function map(
        string $source,
        string $naturalKey,
        string $sourceUrl,
        CarbonInterface $fetchedAt,
        array $payload,
    ): void {
        // Intentionally no domain projection — provenance lives in ingest_records.
    }
}
