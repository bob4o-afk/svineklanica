<?php

declare(strict_types=1);

namespace Modules\Presentation\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Modules\Detection\Models\Flag;
use Modules\Presentation\Data\FlagFeedFilterData;
use Modules\Procurement\Models\Company;
use Modules\Procurement\Models\ContractingAuthority;
use Modules\Procurement\Models\PriceSnapshot;
use Modules\Procurement\Models\Tender;

/**
 * The read seam for the citizen-facing API (the BFF). It is allowed to query the
 * Procurement + Detection domain models directly — that coupling is the whole point
 * of a presentation/read layer, and it keeps the contract-shaping logic in one place
 * instead of leaking into every domain module.
 */
interface PresentationRepository
{
    /** @return LengthAwarePaginator<int, Flag> */
    public function paginateFlags(FlagFeedFilterData $filter): LengthAwarePaginator;

    public function countTenders(): int;

    public function countFlags(): int;

    /**
     * Every flag that carries a location (denormalised `region_code`), for pinning on the map.
     * Lightweight: only the columns the map needs.
     *
     * @return Collection<int, Flag>
     */
    public function flagMapPoints(): Collection;

    public function findFlag(string $publicId): ?Flag;

    public function findAuthority(string $publicId): ?ContractingAuthority;

    /** @return LengthAwarePaginator<int, Flag> */
    public function flagsForAuthority(ContractingAuthority $authority, int $perPage = 20): LengthAwarePaginator;

    /** Critical-severity flags for an authority across ALL pages (not just the loaded one). */
    public function countCriticalFlagsForAuthority(ContractingAuthority $authority): int;

    public function findCompanyByEik(string $eik): ?Company;

    public function findCompanyByPublicId(string $publicId): ?Company;

    /** @return LengthAwarePaginator<int, Flag> */
    public function flagsForCompany(Company $company, int $perPage = 20): LengthAwarePaginator;

    /** Critical-severity flags for a company across ALL pages (not just the loaded one). */
    public function countCriticalFlagsForCompany(Company $company): int;

    /** @return Collection<int, Company> */
    public function relatedCompanies(Company $company, int $limit = 4): Collection;

    /**
     * Price snapshots for a product series, oldest first.
     *
     * @return Collection<int, PriceSnapshot>
     */
    public function priceSnapshots(string $productKey): Collection;

    /**
     * Per-region flag counts, optionally filtered to one sector.
     *
     * @return array<int, array{region_code: string, flag_count: int}>
     */
    public function regionAggregates(?string $sector): array;

    /** @return Collection<int, ContractingAuthority> */
    public function searchAuthorities(string $q, int $limit = 10): Collection;

    /** @return Collection<int, Company> */
    public function searchCompanies(string $q, int $limit = 10): Collection;

    /** @return Collection<int, Tender> */
    public function searchTenders(string $q, int $limit = 10): Collection;

    /**
     * Semantic search: the rows nearest the query embedding by pgvector cosine
     * distance (CLAUDE.md §1.2 — "close results"). The embedding has
     * config('vector.dimensions') floats and comes from the same model the
     * `search:embed` command used. Rows without an embedding are excluded.
     *
     * @param  list<float>  $embedding
     * @return Collection<int, ContractingAuthority>
     */
    public function searchAuthoritiesByVector(array $embedding, int $limit = 10): Collection;

    /**
     * @param  list<float>  $embedding
     * @return Collection<int, Company>
     */
    public function searchCompaniesByVector(array $embedding, int $limit = 10): Collection;

    /**
     * @param  list<float>  $embedding
     * @return Collection<int, Tender>
     */
    public function searchTendersByVector(array $embedding, int $limit = 10): Collection;
}
