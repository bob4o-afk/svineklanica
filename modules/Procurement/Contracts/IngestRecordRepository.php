<?php

declare(strict_types=1);

namespace Modules\Procurement\Contracts;

use Carbon\CarbonInterface;
use Modules\Procurement\Models\IngestRecord;

/**
 * Provenance/staging store for the scraper's NDJSON contract (scraping.md §2).
 * The ONLY place that touches the ingest_records table (backend.md §2).
 */
interface IngestRecordRepository
{
    /**
     * Idempotent upsert on (source, natural_key).
     *
     * @param array<string, mixed> $payload
     */
    public function upsert(
        string $source,
        string $naturalKey,
        string $sourceUrl,
        CarbonInterface $fetchedAt,
        int $schemaVersion,
        array $payload,
    ): IngestRecord;

    public function markIngested(IngestRecord $record): void;

    public function markSkipped(IngestRecord $record, string $reason): void;
}
