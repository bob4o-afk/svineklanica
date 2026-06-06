<?php

declare(strict_types=1);

namespace App\Shared\Contracts;

use App\Shared\DTO\CancelledTenderData;
use App\Shared\DTO\CompanyWinData;
use App\Shared\DTO\PriceObservationData;
use App\Shared\DTO\TenderSubjectData;

/**
 * Read port the Detection module uses to pull the procurement data its detectors
 * run on, without importing Procurement's Eloquent models (backend.md §1 — modules
 * talk through shared contracts returning shared DTOs). Implemented in Procurement,
 * bound in its service provider.
 */
interface ProcurementReadPort
{
    /** @return array<int, PriceObservationData> Every priced line-item observation. */
    public function priceObservations(): array;

    /** @return array<int, CompanyWinData> Companies with at least $minWins won tenders. */
    public function serialWinners(int $minWins): array;

    /** @return array<int, CancelledTenderData> Tenders cancelled or terminated by the authority. */
    public function cancelledTenders(): array;

    /**
     * Resolve a source's tenders by natural_key, so the AI verdict ingest can attach
     * each verdict's Flag to the right tender subject (AnalyzeIngest).
     *
     * @return array<string, TenderSubjectData> keyed by natural_key
     */
    public function tenderSubjectsByNaturalKey(string $source): array;
}
