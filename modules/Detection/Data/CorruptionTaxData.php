<?php

declare(strict_types=1);

namespace Modules\Detection\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * Result of the corruption-tax calculator (CLAUDE.md — "10 € of your taxes bought
 * a 200k laptop"): the share of public spend that carries a red flag, projected
 * onto the user's tax contribution. Every number is derived live from ingested
 * data; `flagged` means "a detector fired", not "proven stolen" (honesty tiers).
 */
#[TypeScript]
final class CorruptionTaxData extends Data
{
    /**
     * @param  array<int, SphereSpendShareData>  $perSphere
     * @param  array<int, FlaggedCaseShareData>  $topCases
     */
    public function __construct(
        public float $taxesPaid,
        public string $currency,
        public float $corruptionRate,          // 0..1 — flaggedSpend / totalSpend
        public float $userCorruptionAmount,    // taxesPaid * corruptionRate
        public float $totalSpend,
        public float $flaggedSpend,
        public array $perSphere,
        public array $topCases,
    ) {}
}
