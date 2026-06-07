<?php

declare(strict_types=1);

namespace Modules\Procurement\Repositories;

use App\Shared\Contracts\SpendReadPort;
use App\Shared\DTO\FlaggedCaseData;
use App\Shared\DTO\SpendSummaryData;
use App\Shared\DTO\SphereSpendData;
use App\Shared\Enums\CorruptionCategory;
use App\Shared\Enums\Sphere;
use Illuminate\Support\Facades\DB;

/**
 * Procurement's side of the {@see SpendReadPort} seam — sums its own tenders +
 * payments tables for the corruption-tax calculator (backend.md §1/§2). Flagged
 * spend is SCORE-WEIGHTED: a flagged record contributes `amount × weight`, where
 * weight ∈ [0,1] is the caller's suspicion confidence. Parameterized queries only
 * (security.md §6).
 */
final class EloquentSpendReadRepository implements SpendReadPort
{
    private const CURRENCY = 'BGN'; // nominal; FX/VAT normalization is a follow-up (data-sources.md §3)

    private const TOP_CASES = 5;

    public function spendSummary(array $tenderWeights, array $paymentWeights): SpendSummaryData
    {
        $tenderTotal = (float) DB::table('tenders')->sum('value');
        $paymentTotal = (float) DB::table('payments')->sum('amount');

        // sphere int (or '_' for null) => ['total' => x, 'flagged' => y]
        $acc = [];
        $bump = function (?int $sphere, string $bucket, float $amount) use (&$acc): void {
            $key = $sphere === null ? '_' : (string) $sphere;
            $acc[$key] ??= ['total' => 0.0, 'flagged' => 0.0];
            $acc[$key][$bucket] += $amount;
        };

        // Totals per sphere (all rows, full value — the denominator is unweighted).
        foreach (DB::table('tenders')->groupBy('sphere')->selectRaw('sphere, COALESCE(SUM(value),0) as amt')->get() as $r) {
            $bump($r->sphere !== null ? (int) $r->sphere : null, 'total', (float) $r->amt);
        }
        foreach (DB::table('payments')->groupBy('sphere')->selectRaw('sphere, COALESCE(SUM(amount),0) as amt')->get() as $r) {
            $bump($r->sphere !== null ? (int) $r->sphere : null, 'total', (float) $r->amt);
        }

        // Flagged spend = Σ amount × weight, accumulated per sphere + as headline cases.
        $cases = [];
        $flaggedSpend = $this->accumulateFlagged('tenders', 'value', 'tender', $tenderWeights, $bump, $cases)
            + $this->accumulateFlagged('payments', 'amount', 'payment', $paymentWeights, $bump, $cases);

        $perSphere = [];
        foreach ($acc as $key => $sums) {
            $perSphere[] = new SphereSpendData(
                sphere: $key === '_' ? null : Sphere::tryFrom((int) $key),
                total: $sums['total'],
                flagged: $sums['flagged'],
            );
        }
        usort($perSphere, static fn (SphereSpendData $a, SphereSpendData $b): int => $b->flagged <=> $a->flagged);

        // Biggest CONTRIBUTORS first (value × weight), not biggest raw contracts.
        usort($cases, static fn (FlaggedCaseData $a, FlaggedCaseData $b): int => ($b->amount * $b->weight) <=> ($a->amount * $a->weight));

        return new SpendSummaryData(
            totalSpend: $tenderTotal + $paymentTotal,
            flaggedSpend: $flaggedSpend,
            currency: self::CURRENCY,
            perSphere: $perSphere,
            topCases: array_slice($cases, 0, self::TOP_CASES),
        );
    }

    /**
     * Sum `amount × weight` over the flagged rows of one table, feeding the
     * per-sphere accumulator and the headline-case list. Returns the weighted total.
     *
     * @param  array<int, float>  $weights  id => weight (0..1)
     * @param  array<int, FlaggedCaseData>  $cases  (by reference)
     */
    private function accumulateFlagged(
        string $table,
        string $amountColumn,
        string $kind,
        array $weights,
        callable $bump,
        array &$cases,
    ): float {
        if ($weights === []) {
            return 0.0;
        }

        $sum = 0.0;
        $rows = DB::table($table)
            ->whereIn('id', array_keys($weights))
            ->select(['id', 'title', "{$amountColumn} as amount", 'currency', 'sphere', 'category', 'source_url'])
            ->get();

        foreach ($rows as $r) {
            $weight = max(0.0, min(1.0, (float) ($weights[(int) $r->id] ?? 0.0)));
            $amount = (float) $r->amount;
            $sum += $amount * $weight;
            $bump($r->sphere !== null ? (int) $r->sphere : null, 'flagged', $amount * $weight);

            if ($amount > 0.0) {
                $cases[] = new FlaggedCaseData(
                    kind: $kind,
                    subjectId: (int) $r->id,
                    title: (string) $r->title,
                    amount: $amount,
                    currency: (string) ($r->currency ?? self::CURRENCY),
                    sourceUrl: (string) $r->source_url,
                    sphere: $r->sphere !== null ? Sphere::tryFrom((int) $r->sphere) : null,
                    category: $r->category !== null ? CorruptionCategory::tryFrom((int) $r->category) : null,
                    weight: $weight,
                );
            }
        }

        return $sum;
    }
}
