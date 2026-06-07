<?php

declare(strict_types=1);

namespace Modules\Presentation\Repositories;

use App\Shared\Enums\FlagSeverity;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Detection\Models\Flag;
use Modules\Presentation\Contracts\PresentationRepository;
use Modules\Presentation\Data\FlagFeedFilterData;
use Modules\Procurement\Models\Company;
use Modules\Procurement\Models\ContractingAuthority;
use Modules\Procurement\Models\PriceSnapshot;
use Modules\Procurement\Models\Tender;

final class EloquentPresentationRepository implements PresentationRepository
{
    public function paginateFlags(FlagFeedFilterData $filter): LengthAwarePaginator
    {
        $query = $this->flagQuery()
            ->when($filter->typeValues !== [], fn (Builder $q) => $q->whereIn('type', $filter->typeValues))
            ->when($filter->severities !== [], fn (Builder $q) => $q->whereIn('severity', $filter->severities))
            ->when($filter->sectors !== [], fn (Builder $q) => $q->whereIn('sector', $filter->sectors))
            ->when($filter->region !== null, fn (Builder $q) => $q->where('region_code', $filter->region))
            ->when($filter->q !== null, function (Builder $q) use ($filter): void {
                $term = '%'.self::escapeLike((string) $filter->q).'%';
                $q->where(function (Builder $inner) use ($term): void {
                    $inner->where('title', 'ilike', $term)
                        ->orWhere('explanation_bg', 'ilike', $term);
                });
            });

        // Each sort fully tie-breaks down the same three keys (recency / suspicion score /
        // view_count) so the order is total and stable — only the leading key changes.
        match ($filter->sort) {
            'severity' => $query->orderByDesc('score')->orderByDesc('detected_at')->orderByDesc('view_count'),
            'views' => $query->orderByDesc('view_count')->orderByDesc('detected_at')->orderByDesc('score'),
            // newest (default): latest first, then by severity (score), finally by views.
            default => $query->orderByDesc('detected_at')->orderByDesc('score')->orderByDesc('view_count'),
        };

        return $query->paginate($filter->perPage, ['*'], 'page', $filter->page);
    }

    public function countTenders(): int
    {
        return Tender::query()->count();
    }

    public function countFlags(): int
    {
        return Flag::query()->count();
    }

    /** @return Collection<int, Flag> */
    public function flagMapPoints(): Collection
    {
        return Flag::query()
            ->whereNotNull('region_code')
            ->where('region_code', '!=', '')
            ->orderByDesc('severity')
            ->get(['id', 'public_id', 'severity', 'type', 'region_code', 'title']);
    }

    public function findFlag(string $publicId): ?Flag
    {
        if (! Str::isUuid($publicId)) {
            return null;
        }

        return $this->flagQuery()->where('public_id', $publicId)->first();
    }

    public function findAuthority(string $publicId): ?ContractingAuthority
    {
        if (! Str::isUuid($publicId)) {
            return null;
        }

        return ContractingAuthority::query()->where('public_id', $publicId)->first();
    }

    public function flagsForAuthority(ContractingAuthority $authority, int $perPage = 20): LengthAwarePaginator
    {
        return $this->authorityFlagsQuery($authority)
            ->orderByDesc('score')
            ->orderByDesc('detected_at')
            ->paginate($perPage);
    }

    public function countCriticalFlagsForAuthority(ContractingAuthority $authority): int
    {
        return $this->authorityFlagsQuery($authority)
            ->where('severity', FlagSeverity::Critical->value)
            ->count();
    }

    /** @return Builder<Flag> */
    private function authorityFlagsQuery(ContractingAuthority $authority): Builder
    {
        $tenderIds = Tender::query()->where('contracting_authority_id', $authority->id)->pluck('id')->all();

        return $this->flagQuery()
            ->where(function (Builder $q) use ($authority, $tenderIds): void {
                $q->where(fn (Builder $a) => $a->where('subject_type', 'authority')->where('subject_id', $authority->id));
                if ($tenderIds !== []) {
                    $q->orWhere(fn (Builder $t) => $t->where('subject_type', 'tender')->whereIn('subject_id', $tenderIds));
                }
            });
    }

    public function findCompanyByEik(string $eik): ?Company
    {
        return Company::query()->where('eik', $eik)->first();
    }

    public function findCompanyByPublicId(string $publicId): ?Company
    {
        if (! Str::isUuid($publicId)) {
            return null;
        }

        return Company::query()->where('public_id', $publicId)->first();
    }

    public function flagsForCompany(Company $company, int $perPage = 20): LengthAwarePaginator
    {
        return $this->companyFlagsQuery($company)
            ->orderByDesc('score')
            ->orderByDesc('detected_at')
            ->paginate($perPage);
    }

    public function countCriticalFlagsForCompany(Company $company): int
    {
        return $this->companyFlagsQuery($company)
            ->where('severity', FlagSeverity::Critical->value)
            ->count();
    }

    /** @return Builder<Flag> */
    private function companyFlagsQuery(Company $company): Builder
    {
        $wonTenderIds = Tender::query()->where('winner_company_id', $company->id)->pluck('id')->all();

        return $this->flagQuery()
            ->where(function (Builder $q) use ($company, $wonTenderIds): void {
                $q->where(fn (Builder $c) => $c->where('subject_type', 'company')->where('subject_id', $company->id));
                if ($wonTenderIds !== []) {
                    $q->orWhere(fn (Builder $t) => $t->where('subject_type', 'tender')->whereIn('subject_id', $wonTenderIds));
                }
            });
    }

    public function relatedCompanies(Company $company, int $limit = 4): Collection
    {
        // Shell-cluster heuristic: other companies that won tenders from the SAME
        // authorities this company did (shared contracting authority → worth a look).
        $authorityIds = Tender::query()
            ->where('winner_company_id', $company->id)
            ->whereNotNull('contracting_authority_id')
            ->pluck('contracting_authority_id')
            ->unique()
            ->all();

        if ($authorityIds === []) {
            return new Collection;
        }

        $relatedIds = Tender::query()
            ->whereIn('contracting_authority_id', $authorityIds)
            ->whereNotNull('winner_company_id')
            ->where('winner_company_id', '!=', $company->id)
            ->pluck('winner_company_id')
            ->unique()
            ->take($limit)
            ->all();

        if ($relatedIds === []) {
            return new Collection;
        }

        return Company::query()->whereIn('id', $relatedIds)->get();
    }

    public function priceSnapshots(string $productKey): Collection
    {
        return PriceSnapshot::query()
            ->where('product_key', $productKey)
            ->with(['tenderItem.tender.winner'])
            ->orderBy('captured_at')
            ->get();
    }

    public function regionAggregates(?string $sector): array
    {
        /** @var array<int, array{region_code: string, flag_count: int}> */
        return Flag::query()
            ->whereNotNull('region_code')
            ->when($sector !== null, fn (Builder $q) => $q->where('sector', $sector))
            ->groupBy('region_code')
            ->select('region_code', DB::raw('count(*) as flag_count'))
            ->get()
            ->map(static fn ($row): array => [
                'region_code' => (string) $row->region_code,
                'flag_count' => (int) $row->flag_count,
            ])
            ->all();
    }

    public function searchAuthorities(string $q, int $limit = 10): Collection
    {
        $term = '%'.self::escapeLike($q).'%';

        return ContractingAuthority::query()
            ->where('name', 'ilike', $term)
            ->limit($limit)
            ->get();
    }

    public function searchCompanies(string $q, int $limit = 10): Collection
    {
        $term = '%'.self::escapeLike($q).'%';

        return Company::query()
            ->where(function (Builder $inner) use ($term): void {
                $inner->where('name', 'ilike', $term)
                    ->orWhere('eik', 'ilike', $term);
            })
            ->limit($limit)
            ->get();
    }

    public function searchTenders(string $q, int $limit = 10): Collection
    {
        $term = '%'.self::escapeLike($q).'%';

        return Tender::query()
            ->where('title', 'ilike', $term)
            ->limit($limit)
            ->get();
    }

    public function searchAuthoritiesByVector(array $embedding, int $limit = 10): Collection
    {
        return $this->nearest(ContractingAuthority::query(), 'name_embedding', $embedding, $limit);
    }

    public function searchCompaniesByVector(array $embedding, int $limit = 10): Collection
    {
        return $this->nearest(Company::query(), 'name_embedding', $embedding, $limit);
    }

    public function searchTendersByVector(array $embedding, int $limit = 10): Collection
    {
        return $this->nearest(Tender::query(), 'description_embedding', $embedding, $limit);
    }

    /**
     * Order a query by pgvector cosine distance to the given embedding and take the
     * nearest `$limit`. The vector is passed as a bound parameter cast to `vector`
     * (`?::vector`) — never string-concatenated into SQL (security.md §6) — and the
     * `<=>` operator uses the cosine index from config/vector.php. Rows without an
     * embedding are excluded so they don't pollute the "close results".
     *
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     * @param  list<float>  $embedding
     * @return Collection<int, TModel>
     */
    private function nearest(Builder $query, string $column, array $embedding, int $limit): Collection
    {
        if ($embedding === []) {
            return new Collection;
        }

        $literal = '['.implode(',', array_map(static fn ($v): string => (string) (float) $v, $embedding)).']';

        return $query
            ->whereNotNull($column)
            ->orderByRaw("{$column} <=> ?::vector", [$literal])
            ->limit($limit)
            ->get();
    }

    /**
     * Escape the ILIKE wildcard metacharacters (`%`, `_`) and the escape char itself
     * so a citizen's literal search term ('50%', a code with '_') matches literally
     * instead of being treated as a wildcard. Postgres ILIKE escapes with `\` by default.
     */
    private static function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }

    /**
     * Base flag query with the morph subject + its relations eager-loaded, so
     * FlagPostData can project authority/company/tender without N+1.
     *
     * @return Builder<Flag>
     */
    private function flagQuery(): Builder
    {
        return Flag::query()->with(['subject' => function (MorphTo $morphTo): void {
            $morphTo->morphWith([
                Tender::class => ['authority', 'winner'],
                Company::class => ['wonTenders.authority', 'wonTenders.winner'],
                ContractingAuthority::class => ['tenders.authority', 'tenders.winner'],
            ]);
        }]);
    }
}
