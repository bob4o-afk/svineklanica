<?php

declare(strict_types=1);

namespace Modules\Procurement\Contracts;

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

    /** Replace a tender's line items (idempotent re-ingest). @param array<int, array<string, mixed>> $items */
    public function syncItems(Tender $tender, array $items): void;
}
