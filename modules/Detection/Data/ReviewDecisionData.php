<?php

declare(strict_types=1);

namespace Modules\Detection\Data;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/** Admin approve payload (contract.ts ReviewDecision). */
#[MapInputName(SnakeCaseMapper::class)]
final class ReviewDecisionData extends Data
{
    /** @param array<int, string>|null $tags */
    public function __construct(
        public ?string $title = null,
        public ?string $explanation_bg = null,
        public ?string $note = null,
        public ?array $tags = null,
    ) {}
}
