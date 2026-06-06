<?php

declare(strict_types=1);

namespace Modules\Procurement\Support;

/**
 * Normalizes a free-text item description into a clustering key so the
 * price-discrepancy detector (CLAUDE.md §1.1.1) can group the same product written
 * five different ways. Cheap first pass (data-sources.md §3): lower-case, strip
 * punctuation, collapse whitespace. Upgrade to CPV / fuzzy / vector only if time allows.
 */
final class ProductKey
{
    public static function normalize(?string $description): ?string
    {
        if ($description === null) {
            return null;
        }

        $key = mb_strtolower(trim($description), 'UTF-8');
        $key = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $key) ?? $key; // drop punctuation
        $key = preg_replace('/\s+/u', ' ', $key) ?? $key;              // collapse whitespace
        $key = trim($key);

        return $key === '' ? null : $key;
    }
}
