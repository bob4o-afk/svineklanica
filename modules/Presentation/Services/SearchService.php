<?php

declare(strict_types=1);

namespace Modules\Presentation\Services;

use App\Support\Google\GoogleAiClient;
use Illuminate\Support\Collection;
use Modules\Presentation\Contracts\PresentationRepository;
use Modules\Presentation\Data\AuthorityRefData;
use Modules\Presentation\Data\CompanyRefData;
use Modules\Presentation\Data\SearchResultsData;
use Modules\Presentation\Data\TenderRefData;
use Modules\Procurement\Models\Company;
use Modules\Procurement\Models\ContractingAuthority;
use Modules\Procurement\Models\Tender;

/**
 * The "AI search agent" behind the citizen search box (CLAUDE.md §1.2): it maps a
 * raw query to close results. Google (GOOGLE_API_KEY) does the guessing — Gemini
 * optionally cleans the query, then the SAME embedding model turns it into a
 * vector and pgvector returns the nearest tenders/companies/authorities.
 *
 * It degrades to the keyword (ILIKE) search whenever Google is unavailable OR the
 * vector index is still empty (nothing embedded yet), so the box always works.
 */
final class SearchService
{
    public function __construct(
        private readonly PresentationRepository $repo,
        private readonly GoogleAiClient $google,
    ) {}

    public function search(string $q, int $perType = 10): SearchResultsData
    {
        [$authorities, $companies, $tenders] = $this->resolve($q, $perType);

        return new SearchResultsData(
            authorities: $authorities->map(static fn ($a): AuthorityRefData => AuthorityRefData::fromModel($a))->all(),
            companies: $companies->map(static fn ($c): CompanyRefData => CompanyRefData::fromModel($c))->all(),
            tenders: $tenders->map(static fn ($t): TenderRefData => TenderRefData::fromModel($t))->all(),
        );
    }

    /**
     * @return array{0: Collection<int, ContractingAuthority>, 1: Collection<int, Company>, 2: Collection<int, Tender>}
     */
    private function resolve(string $q, int $perType): array
    {
        if (! $this->google->configured()) {
            return $this->keyword($q, $perType);
        }

        $vector = $this->google->embed($this->google->mapQuery($q), 'RETRIEVAL_QUERY');
        if ($vector === null || $vector === []) {
            return $this->keyword($q, $perType);
        }

        $results = [
            $this->repo->searchAuthoritiesByVector($vector, $perType),
            $this->repo->searchCompaniesByVector($vector, $perType),
            $this->repo->searchTendersByVector($vector, $perType),
        ];

        // Nothing embedded yet (or no neighbours) — fall back so the box still works.
        if ($results[0]->isEmpty() && $results[1]->isEmpty() && $results[2]->isEmpty()) {
            return $this->keyword($q, $perType);
        }

        return $results;
    }

    /**
     * @return array{0: Collection<int, ContractingAuthority>, 1: Collection<int, Company>, 2: Collection<int, Tender>}
     */
    private function keyword(string $q, int $perType): array
    {
        return [
            $this->repo->searchAuthorities($q, $perType),
            $this->repo->searchCompanies($q, $perType),
            $this->repo->searchTenders($q, $perType),
        ];
    }
}
