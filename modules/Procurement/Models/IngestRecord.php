<?php

declare(strict_types=1);

namespace Modules\Procurement\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Landing row for the scraper's NDJSON contract (scraping.md §2). Internal /
 * staging — NO public_id (backend.md §7). `ingest:run` upserts on
 * (source, natural_key).
 */
final class IngestRecord extends Model
{
    protected $fillable = [
        'source',
        'natural_key',
        'source_url',
        'fetched_at',
        'schema_version',
        'payload',
        'raw_hash',
        'status',
        'skip_reason',
        'ingested_at',
    ];

    protected function casts(): array
    {
        return [
            'fetched_at' => 'datetime',
            'ingested_at' => 'datetime',
            'schema_version' => 'integer',
            'payload' => 'array',
        ];
    }
}
