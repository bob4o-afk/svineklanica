<?php

declare(strict_types=1);

namespace Modules\Detection\Data;

use App\Shared\Enums\CorruptionCategory;
use App\Shared\Enums\Sphere;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** One headline flagged case for the calculator's "where it went" list — with its source. */
#[TypeScript]
final class FlaggedCaseShareData extends Data
{
    public function __construct(
        public string $kind,        // 'tender' | 'payment'
        public string $title,
        public float $amount,       // full contract value (headline)
        public string $currency,
        public string $sourceUrl,
        public ?Sphere $sphere,
        public ?CorruptionCategory $category,
        public int $score,          // suspicion score 0–100 (the weight, ×100) — why it counts partially
        public float $userShare,    // the user's taxes attributable to this case (score-weighted)
        public ?string $flagPublicId, // link to the readable flag-post (null if it somehow has none)
    ) {}
}
