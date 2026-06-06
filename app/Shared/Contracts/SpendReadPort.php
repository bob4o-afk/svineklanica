<?php

declare(strict_types=1);

namespace App\Shared\Contracts;

use App\Shared\DTO\SpendSummaryData;

/**
 * Read port for spend aggregates (tenders + payments), used by the corruption-tax
 * calculator without importing Procurement's models (backend.md §1). The caller
 * (Detection) supplies the internal ids of flagged tenders/payments — it owns the
 * flags; Procurement owns the amounts. Implemented in Procurement, bound there.
 */
interface SpendReadPort
{
    /**
     * Flagged spend is score-weighted: each flagged record contributes
     * `amount × weight`, where weight ∈ [0,1] is the caller's confidence
     * (suspicion score / 100). A weight of 1.0 reproduces the old binary count.
     *
     * @param  array<int, float>  $tenderWeights  flagged tender id => weight (0..1)
     * @param  array<int, float>  $paymentWeights  flagged payment id => weight (0..1)
     */
    public function spendSummary(array $tenderWeights, array $paymentWeights): SpendSummaryData;
}
