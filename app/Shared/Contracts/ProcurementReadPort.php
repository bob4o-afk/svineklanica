<?php

declare(strict_types=1);

namespace App\Shared\Contracts;

use App\Shared\DTO\CancelledTenderData;
use App\Shared\DTO\CompanyWinData;
use App\Shared\DTO\PriceObservationData;

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
}
