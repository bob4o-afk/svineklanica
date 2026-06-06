<?php

declare(strict_types=1);

namespace App\Shared\DTO;

/**
 * Aggregate spend the corruption-tax calculator runs on: total public spend
 * (tenders + payments) vs the slice that carries a red flag, plus a per-sphere
 * breakdown and the biggest flagged cases. Computed in Procurement (it owns the
 * amounts) and consumed across the seam (backend.md §1).
 *
 * NB: amounts are summed in nominal currency (BGN-dominant); FX/VAT normalization
 * is a known follow-up (data-sources.md §3) — the rate is a ratio so it's robust
 * to that as long as numerator and denominator share the mix.
 */
final readonly class SpendSummaryData
{
    /**
     * @param  array<int, SphereSpendData>  $perSphere
     * @param  array<int, FlaggedCaseData>  $topCases  biggest flagged cases, amount desc
     */
    public function __construct(
        public float $totalSpend,
        public float $flaggedSpend,
        public string $currency,
        public array $perSphere,
        public array $topCases,
    ) {}
}
