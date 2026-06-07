<?php

declare(strict_types=1);

namespace Modules\Presentation\Http\Controllers;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Modules\Detection\Models\Flag;
use Modules\Presentation\Contracts\PresentationRepository;
use Modules\Presentation\Data\AuthorityDetailData;
use Modules\Presentation\Data\AuthorityRefData;
use Modules\Presentation\Data\CompanyDetailData;
use Modules\Presentation\Data\CompanyRefData;
use Modules\Presentation\Data\EntityStatsData;
use Modules\Presentation\Data\PaginatedFlagPostData;
use Modules\Presentation\Data\SearchResultsData;
use Modules\Presentation\Services\SearchService;
use Spatie\LaravelData\Optional;

/** Entity profiles (authority, company) + global search (contract `/authorities`, `/companies`, `/search`). */
final class EntityController
{
    /** Below this the search box is just noise; mirror the frontend's SEARCH_MIN_LENGTH. */
    private const SEARCH_MIN_LENGTH = 2;

    public function __construct(
        private readonly PresentationRepository $repo,
        private readonly SearchService $search,
    ) {}

    public function authority(string $publicId): AuthorityDetailData
    {
        $authority = $this->repo->findAuthority($publicId);
        abort_if($authority === null, 404);

        $flags = $this->repo->flagsForAuthority($authority);

        return new AuthorityDetailData(
            authority: AuthorityRefData::fromModel($authority),
            stats: $this->stats($flags, $this->repo->countCriticalFlagsForAuthority($authority)),
            flags: PaginatedFlagPostData::fromPaginator($flags),
        );
    }

    public function company(string $eik): CompanyDetailData
    {
        $company = $this->repo->findCompanyByEik($eik);
        abort_if($company === null, 404);

        $flags = $this->repo->flagsForCompany($company);

        return new CompanyDetailData(
            company: CompanyRefData::fromModel($company),
            stats: $this->stats($flags, $this->repo->countCriticalFlagsForCompany($company)),
            related: $this->repo->relatedCompanies($company)
                ->map(static fn ($c): CompanyRefData => CompanyRefData::fromModel($c))
                ->all(),
            flags: PaginatedFlagPostData::fromPaginator($flags),
        );
    }

    public function search(Request $request): SearchResultsData
    {
        $q = trim((string) $request->query('q', ''));
        if (mb_strlen($q) < self::SEARCH_MIN_LENGTH) {
            return new SearchResultsData(authorities: [], companies: [], tenders: []);
        }

        // Semantic "close results" via Google embeddings + pgvector, with a keyword
        // fallback baked into the service (CLAUDE.md §1.2).
        return $this->search->search($q);
    }

    /**
     * Both counters span the whole result set: flagCount is the paginator total, and
     * criticalCount is a dedicated COUNT over the same scope (not the loaded page), so
     * the two never disagree once an entity has more flags than fit on one page.
     * total_value is omitted (optional in the contract).
     *
     * @param  LengthAwarePaginator<int, Flag>  $flags
     */
    private function stats(LengthAwarePaginator $flags, int $criticalCount): EntityStatsData
    {
        return new EntityStatsData(
            flagCount: $flags->total(),
            criticalCount: $criticalCount,
            totalValue: Optional::create(),
        );
    }
}
