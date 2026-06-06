<?php

declare(strict_types=1);

namespace Modules\Procurement\Ingest;

use Carbon\CarbonInterface;

/**
 * Maps one canonical NDJSON `payload` (contract.py v2) onto domain rows. One
 * implementation per `record_type` (tender, payment, …); the registry dispatches.
 * Adding a new source type = one new typed block in contract.py + one new mapper
 * here, nothing else (the "meet in the middle" reusable seam).
 */
interface PayloadMapper
{
    /** The contract.py RecordType this mapper handles, e.g. "tender". */
    public function recordType(): string;

    /**
     * Persist the record's domain rows. Runs inside the ingest transaction.
     *
     * @param  array<string, mixed>  $payload  The canonical, normalized payload.
     */
    public function map(
        string $source,
        string $naturalKey,
        string $sourceUrl,
        CarbonInterface $fetchedAt,
        array $payload,
    ): void;
}
