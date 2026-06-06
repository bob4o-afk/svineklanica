<?php

declare(strict_types=1);

namespace Modules\Procurement\Contracts;

use Carbon\CarbonInterface;
use Modules\Procurement\Models\Company;
use Modules\Procurement\Models\ContractingAuthority;
use Modules\Procurement\Models\Tender;

/**
 * Write path for the procurement aggregate during ingest. All upserts are
 * idempotent on a natural key (backend.md §12). The ONLY place that touches the
 * tenders / authorities / companies / items tables (backend.md §2).
 */
interface TenderIngestRepository
{
    /** @param array<string, mixed> $data Matches on EIK, else name. */
    public function upsertAuthority(array $data): ?ContractingAuthority;

    /** @param array<string, mixed> $data Matches on EIK, else name. */
    public function upsertCompany(array $data): ?Company;

    /** @param array<string, mixed> $attributes Matches on (source, natural_key). */
    public function upsertTender(string $source, string $naturalKey, array $attributes): Tender;

    /**
     * Replace a tender's line items AND write a price snapshot per priced item
     * (CLAUDE.md §1.2 — feeds the price-over-time graph + price detector). Idempotent:
     * a re-ingest wipes the tender's old items + snapshots first.
     *
     * @param  array<int, array<string, mixed>>  $items
     * @param  CarbonInterface  $capturedAt  point-in-time of this snapshot (the record's fetched_at)
     */
    public function syncItems(Tender $tender, array $items, CarbonInterface $capturedAt): void;
}
