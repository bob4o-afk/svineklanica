<?php

declare(strict_types=1);

namespace Modules\Presentation\Data;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/** Contract `SerialWinnerGraph` — the winner↔authority network for a company. */
#[MapName(SnakeCaseMapper::class)]
final class SerialWinnerGraphData extends Data
{
    /**
     * @param  GraphNodeData[]  $nodes
     * @param  GraphEdgeData[]  $edges
     */
    public function __construct(
        public array $nodes,
        public array $edges,
    ) {}
}
