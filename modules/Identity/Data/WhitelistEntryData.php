<?php

declare(strict_types=1);

namespace Modules\Identity\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * One IP allow-list entry for the read-only admin review (security.md §4). The
 * env is the single source of truth, so every entry's `source` is 'env' and the
 * console can never mutate it — this shape is display-only.
 */
#[TypeScript]
final class WhitelistEntryData extends Data
{
    public function __construct(
        public string $value,
        public string $source,
    ) {}

    /** @param  array{value: string, source: string}  $entry */
    public static function fromArray(array $entry): self
    {
        return new self(
            value: $entry['value'],
            source: $entry['source'],
        );
    }
}
