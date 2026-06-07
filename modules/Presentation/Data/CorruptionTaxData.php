<?php

declare(strict_types=1);

namespace Modules\Presentation\Data;

use Modules\Detection\Data\CorruptionTaxData as CalculatorResult;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * Contract `CorruptionTax` — the citizen calculator result (CLAUDE.md): the share of
 * public spend that carries a red flag, projected onto the taxes a user paid, with
 * headline cases that each link to a readable flag-post. Snake-cased for the React app.
 */
#[MapName(SnakeCaseMapper::class)]
final class CorruptionTaxData extends Data
{
    /**
     * @param  CorruptionSphereData[]  $perSphere
     * @param  CorruptionCaseData[]  $topCases
     */
    public function __construct(
        public MoneyAmountData $taxesPaid,
        public float $corruptionRate,                 // 0..1
        public MoneyAmountData $userCorruptionAmount,
        public MoneyAmountData $totalSpend,
        public MoneyAmountData $flaggedSpend,
        public array $perSphere,
        public array $topCases,
    ) {}

    public static function fromResult(CalculatorResult $r): self
    {
        return new self(
            taxesPaid: new MoneyAmountData($r->taxesPaid, $r->currency, true),
            corruptionRate: $r->corruptionRate,
            userCorruptionAmount: new MoneyAmountData($r->userCorruptionAmount, $r->currency, true),
            totalSpend: new MoneyAmountData($r->totalSpend, $r->currency, true),
            flaggedSpend: new MoneyAmountData($r->flaggedSpend, $r->currency, true),
            perSphere: array_map(
                static fn ($s) => CorruptionSphereData::fromResult($s, $r->currency),
                $r->perSphere,
            ),
            topCases: array_map(
                static fn ($c) => CorruptionCaseData::fromResult($c),
                $r->topCases,
            ),
        );
    }
}
