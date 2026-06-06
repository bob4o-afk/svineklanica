<?php

declare(strict_types=1);

namespace Modules\Detection\Services;

use App\Shared\Contracts\SpendReadPort;
use App\Shared\DTO\FlaggedCaseData;
use App\Shared\DTO\SphereSpendData;
use Modules\Detection\Contracts\FlagRepository;
use Modules\Detection\Data\CorruptionTaxData;
use Modules\Detection\Data\FlaggedCaseShareData;
use Modules\Detection\Data\SphereSpendShareData;

/**
 * The corruption-tax calculator (CLAUDE.md): computes, live from ingested data, the
 * share of public spend carrying a red flag and projects it onto a citizen's taxes —
 * "X% of your N лв funded flagged deals, e.g. this 200k laptop."
 *
 * Orchestrates two seams (backend.md §1): Detection owns the flags (which subjects
 * are flagged), Procurement owns the amounts ({@see SpendReadPort}). Deterministic,
 * re-runnable, every headline case keeps its source_url (data-sources.md §0).
 */
final class CorruptionTaxCalculator
{
    public function __construct(
        private readonly FlagRepository $flags,
        private readonly SpendReadPort $spend,
    ) {}

    public function calculate(float $taxesPaid): CorruptionTaxData
    {
        $flagged = $this->flags->flaggedSubjectScores();
        $summary = $this->spend->spendSummary(
            $this->toWeights($flagged['tender'] ?? []),
            $this->toWeights($flagged['payment'] ?? []),
        );

        $total = $summary->totalSpend;
        $rate = $total > 0.0 ? $summary->flaggedSpend / $total : 0.0;

        return new CorruptionTaxData(
            taxesPaid: $this->money($taxesPaid),
            currency: $summary->currency,
            corruptionRate: round($rate, 4),
            userCorruptionAmount: $this->money($taxesPaid * $rate),
            totalSpend: $this->money($total),
            flaggedSpend: $this->money($summary->flaggedSpend),
            perSphere: array_map(
                fn (SphereSpendData $s): SphereSpendShareData => new SphereSpendShareData(
                    sphere: $s->sphere,
                    total: $this->money($s->total),
                    flagged: $this->money($s->flagged),
                    rate: round($s->total > 0.0 ? $s->flagged / $s->total : 0.0, 4),
                    // Share scaled by TOTAL spend so per-sphere amounts sum to userCorruptionAmount.
                    userCorruptionAmount: $this->money($total > 0.0 ? $taxesPaid * $s->flagged / $total : 0.0),
                ),
                $summary->perSphere,
            ),
            topCases: array_map(
                fn (FlaggedCaseData $c): FlaggedCaseShareData => new FlaggedCaseShareData(
                    kind: $c->kind,
                    title: $c->title,
                    amount: $this->money($c->amount),
                    currency: $c->currency,
                    sourceUrl: $c->sourceUrl,
                    sphere: $c->sphere,
                    category: $c->category,
                    score: (int) round($c->weight * 100),
                    // Score-weighted: your share of this case = taxes × (value × weight) / total.
                    userShare: $this->money($total > 0.0 ? $taxesPaid * $c->amount * $c->weight / $total : 0.0),
                ),
                $summary->topCases,
            ),
        );
    }

    private function money(float $value): float
    {
        return round($value, 2);
    }

    /**
     * Map suspicion scores (0–100) to spend weights (0..1) — keys (subject ids)
     * preserved. A fully-certain flag (100) counts the whole amount; a 50 counts half.
     *
     * @param  array<int, int>  $scores
     * @return array<int, float>
     */
    private function toWeights(array $scores): array
    {
        return array_map(static fn (int $score): float => max(0, min(100, $score)) / 100, $scores);
    }
}
