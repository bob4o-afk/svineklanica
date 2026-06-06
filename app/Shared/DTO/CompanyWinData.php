<?php

declare(strict_types=1);

namespace App\Shared\DTO;

/**
 * A company's win tally, read across tenders. Crosses the Procurement → Detection
 * seam (backend.md §1) for the serial-winner detector (CLAUDE.md §1.1.3). Winning
 * many tenders from few authorities is the tell — both counts ride along.
 */
final readonly class CompanyWinData
{
    public function __construct(
        public int $companyId,
        public string $name,
        public ?string $eik,
        public string $sourceUrl, // always set — the company's, else a won tender's (no source → no flag)
        public int $winCount,
        public int $distinctAuthorities,
    ) {}
}
