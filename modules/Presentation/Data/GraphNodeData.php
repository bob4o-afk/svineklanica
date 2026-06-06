<?php

declare(strict_types=1);

namespace Modules\Presentation\Data;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;
use Spatie\LaravelData\Optional;

/** Contract `GraphNode` — a company or authority node in the serial-winner graph. */
#[MapName(SnakeCaseMapper::class)]
final class GraphNodeData extends Data
{
    public function __construct(
        public string $id,
        public string $kind,
        public string $label,
        public string $publicId,
        public string|Optional $eik,
        public int|Optional $winCount,
        public string|Optional $clusterId,
    ) {}
}
