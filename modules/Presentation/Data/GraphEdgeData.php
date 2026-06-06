<?php

declare(strict_types=1);

namespace Modules\Presentation\Data;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;
use Spatie\LaravelData\Optional;

/** Contract `GraphEdge` — a weighted winner→authority edge (win streak). */
#[MapName(SnakeCaseMapper::class)]
final class GraphEdgeData extends Data
{
    public function __construct(
        public string $id,
        public string $source,
        public string $target,
        public int $weight,
        public string|Optional $label,
    ) {}
}
