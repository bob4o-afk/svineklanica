<?php

declare(strict_types=1);

namespace Modules\Procurement\Repositories;

use Carbon\CarbonInterface;
use Modules\Procurement\Contracts\IngestRecordRepository;
use Modules\Procurement\Models\IngestRecord;

final class EloquentIngestRecordRepository implements IngestRecordRepository
{
    public function upsert(
        string $source,
        string $naturalKey,
        string $sourceUrl,
        CarbonInterface $fetchedAt,
        int $schemaVersion,
        array $payload,
    ): IngestRecord {
        return IngestRecord::updateOrCreate(
            ['source' => $source, 'natural_key' => $naturalKey],
            [
                'source_url' => $sourceUrl,
                'fetched_at' => $fetchedAt,
                'schema_version' => $schemaVersion,
                'payload' => $payload,
                'raw_hash' => hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR)),
            ],
        );
    }

    public function markIngested(IngestRecord $record): void
    {
        $record->forceFill(['status' => 'ingested', 'skip_reason' => null, 'ingested_at' => now()])->save();
    }

    public function markSkipped(IngestRecord $record, string $reason): void
    {
        $record->forceFill(['status' => 'skipped', 'skip_reason' => $reason])->save();
    }
}
