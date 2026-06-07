<?php

declare(strict_types=1);

namespace Modules\Procurement\Data;

/**
 * Honest tally of an embedding run (`search:embed`) — embedded N, skipped M and
 * why (backend.md §8, scraping.md §7). Mirrors {@see IngestSummary}.
 */
final class EmbedSummary
{
    /** @param list<string> $skipReasons */
    public function __construct(
        public readonly string $type,
        public readonly int $embedded = 0,
        public readonly int $skipped = 0,
        public readonly array $skipReasons = [],
    ) {}
}
