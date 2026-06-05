<?php

declare(strict_types=1);

namespace Modules\Procurement\Data;

/**
 * Honest outcome of an ingest run (scraping.md §7): how many lines we read,
 * upserted, and skipped — and WHY each skip happened.
 */
final class IngestSummary
{
    /** @param array<int, string> $skipReasons */
    public function __construct(
        public readonly string $source,
        public readonly string $path,
        public readonly int $read = 0,
        public readonly int $written = 0,
        public readonly int $skipped = 0,
        public readonly array $skipReasons = [],
    ) {}
}
