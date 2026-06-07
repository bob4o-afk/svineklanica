<?php

declare(strict_types=1);

namespace Modules\Presentation\Data;

use Modules\Detection\Data\FlaggedCaseShareData;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;
use Spatie\LaravelData\Optional;

/** Contract `CorruptionTaxCase` — one headline flagged deal + the user's stake in it. */
#[MapName(SnakeCaseMapper::class)]
final class CorruptionCaseData extends Data
{
    public function __construct(
        public string $kind,                  // 'tender' | 'payment'
        public string $title,
        public MoneyAmountData $amount,       // full contract value
        public int $score,                    // suspicion score 0–100
        public string $sourceUrl,             // primary source (TED/registry)
        public MoneyAmountData $userShare,    // the user's taxes attributable to this case
        public string|Optional $flagPublicId, // link to the readable flag-post (/posts/{id})
    ) {}

    public static function fromResult(FlaggedCaseShareData $c): self
    {
        return new self(
            kind: $c->kind,
            title: $c->title,
            amount: new MoneyAmountData($c->amount, $c->currency, true),
            score: $c->score,
            sourceUrl: $c->sourceUrl,
            userShare: new MoneyAmountData($c->userShare, $c->currency, true),
            flagPublicId: $c->flagPublicId ?? Optional::create(),
        );
    }
}
