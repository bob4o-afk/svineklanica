<?php

declare(strict_types=1);

namespace Modules\Procurement\Actions;

use App\Support\Logging\LoggingService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use JsonException;
use Modules\Procurement\Contracts\IngestRecordRepository;
use Modules\Procurement\Data\IngestSummary;
use Modules\Procurement\Ingest\PayloadMapperRegistry;

/**
 * Reads the scraper's NDJSON contract and idempotently upserts it into the DB
 * (scraping.md §2, backend.md §3/§12). Ingest-first: this runs out-of-band,
 * never in a web request and never during the demo.
 *
 * Each NDJSON line is an IngestRecord (apps/scraper/.../contract.py):
 *   { source, natural_key, source_url, fetched_at, schema_version, payload }
 *
 * The `payload` is the canonical v2 envelope: a shared header (record_type,
 * sphere, category, title, authority, winner) plus exactly ONE typed block keyed
 * by `record_type`. This action owns only the provenance row + the dispatch; the
 * per-type domain mapping lives in a {@see PayloadMapperRegistry} mapper, so a new
 * source type is one new typed block (contract.py) + one new mapper, nothing here.
 */
final class IngestSourceAction
{
    private const MAX_STORED_REASONS = 50;

    public function __construct(
        private readonly IngestRecordRepository $records,
        private readonly PayloadMapperRegistry $mappers,
        private readonly LoggingService $log,
    ) {}

    public function execute(string $source, ?string $path = null): IngestSummary
    {
        $path ??= storage_path("ingest/normalized/{$source}.ndjson");

        if (! is_readable($path)) {
            $this->log->warning('ingest: NDJSON not found', ['source' => $source, 'path' => $path]);

            return new IngestSummary($source, $path, skipReasons: ["NDJSON not found at {$path}"]);
        }

        $read = 0;
        $written = 0;
        $skipped = 0;
        $reasons = [];

        $handle = fopen($path, 'rb');

        try {
            $lineNo = 0;
            while (($line = fgets($handle)) !== false) {
                $lineNo++;
                $line = trim($line);
                if ($line === '') {
                    continue;
                }
                $read++;

                $skip = function (string $why) use (&$skipped, &$reasons, $lineNo): void {
                    $skipped++;
                    if (count($reasons) < self::MAX_STORED_REASONS) {
                        $reasons[] = "line {$lineNo}: {$why}";
                    }
                };

                try {
                    /** @var array<string, mixed> $record */
                    $record = json_decode($line, true, 512, JSON_THROW_ON_ERROR);
                } catch (JsonException $e) {
                    $skip('invalid JSON — '.$e->getMessage());

                    continue;
                }

                $missing = $this->missingKeys($record);
                if ($missing !== []) {
                    $skip('missing keys: '.implode(', ', $missing));

                    continue;
                }

                if ($record['source'] !== $source) {
                    $skip("source mismatch (record is '{$record['source']}')");

                    continue;
                }

                try {
                    $this->ingestRecord($source, $record);
                    $written++;
                } catch (\Throwable $e) {
                    // Don't let one bad record kill the run; log it and move on.
                    $this->log->error('ingest: record failed', [
                        'source' => $source,
                        'natural_key' => $record['natural_key'] ?? null,
                        'error' => $e->getMessage(),
                    ]);
                    $skip('upsert failed — '.$e->getMessage());
                }
            }
        } finally {
            fclose($handle);
        }

        $summary = new IngestSummary($source, $path, $read, $written, $skipped, $reasons);

        $this->log->info('ingest: run complete', [
            'source' => $source,
            'read' => $read,
            'written' => $written,
            'skipped' => $skipped,
            'path' => $path,
        ]);

        return $summary;
    }

    /** @param array<string, mixed> $record */
    private function ingestRecord(string $source, array $record): void
    {
        DB::transaction(function () use ($source, $record): void {
            $fetchedAt = Carbon::parse((string) $record['fetched_at']);
            $naturalKey = (string) $record['natural_key'];
            $sourceUrl = (string) $record['source_url'];
            /** @var array<string, mixed> $payload */
            $payload = is_array($record['payload']) ? $record['payload'] : [];

            // 1. Provenance landing row (every record, regardless of type).
            $ingest = $this->records->upsert(
                $source,
                $naturalKey,
                $sourceUrl,
                $fetchedAt,
                (int) ($record['schema_version'] ?? 1),
                $payload,
            );

            // 2. Dispatch the typed payload → domain rows (tender / payment / …).
            $recordType = isset($payload['record_type']) ? (string) $payload['record_type'] : null;
            $this->mappers->for($recordType)->map($source, $naturalKey, $sourceUrl, $fetchedAt, $payload);

            $this->records->markIngested($ingest);
        });
    }

    /** @param array<string, mixed> $record @return array<int, string> */
    private function missingKeys(array $record): array
    {
        $required = ['source', 'natural_key', 'source_url', 'fetched_at', 'payload'];

        return array_values(array_filter(
            $required,
            static fn (string $key): bool => ! array_key_exists($key, $record),
        ));
    }

    private function mapStatus(mixed $status): TenderStatus
    {
        return match (is_string($status) ? strtolower($status) : '') {
            'open' => TenderStatus::Open,
            'awarded' => TenderStatus::Awarded,
            'cancelled' => TenderStatus::Cancelled,
            'terminated' => TenderStatus::Terminated,
            default => TenderStatus::Announced,
        };
    }
}
