<?php

declare(strict_types=1);

namespace Modules\Procurement\Contracts;

use Modules\Procurement\Models\Payment;

/**
 * Write path for budget payments (СЕБРА) during ingest. Idempotent upsert on
 * (source, natural_key) — backend.md §12. The ONLY place that touches the
 * payments table (backend.md §2). Authority/company upserts are shared with
 * {@see TenderIngestRepository} (the single owner of those tables).
 */
interface PaymentIngestRepository
{
    /** @param array<string, mixed> $attributes Matches on (source, natural_key). */
    public function upsertPayment(string $source, string $naturalKey, array $attributes): Payment;
}
