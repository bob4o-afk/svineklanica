<?php

declare(strict_types=1);

namespace Modules\Procurement\Support;

use Modules\Procurement\Models\Company;
use Modules\Procurement\Models\ContractingAuthority;
use Modules\Procurement\Models\Tender;

/**
 * Builds the *searchable document* we embed for each entity — the PHP mirror of
 * the scraper's apps/scraper/src/scraper/searchable.py (title + key entity names
 * + CPV, not the whole row, which adds noise that hurts retrieval). Bulgarian
 * stays Bulgarian (frontend.md §3). This is the text Google embeds, so the search
 * box matches on what a citizen actually types.
 */
final class SearchableText
{
    public static function forTender(Tender $tender): string
    {
        return self::join([
            $tender->title,
            $tender->authority?->name,
            $tender->cpv_code !== null && $tender->cpv_code !== '' ? "CPV {$tender->cpv_code}" : null,
        ]);
    }

    public static function forCompany(Company $company): string
    {
        return self::join([$company->name, $company->owner_name]);
    }

    public static function forAuthority(ContractingAuthority $authority): string
    {
        return self::join([$authority->name, $authority->region]);
    }

    /** @param array<int, string|null> $parts */
    private static function join(array $parts): string
    {
        $clean = array_values(array_filter(
            array_map(static fn (?string $p): string => trim((string) $p), $parts),
            static fn (string $p): bool => $p !== '',
        ));

        return implode(' — ', $clean);
    }
}
