<?php

declare(strict_types=1);

namespace Modules\Presentation\Data;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * Contract `Paginated<FlagPost>` — `{ data, page, per_page, total }`. The frontend's
 * infinite feed drives `getNextPageParam` off these exact keys, so we shape it
 * ourselves rather than emit Laravel's `{ data, meta, links }`.
 */
#[MapName(SnakeCaseMapper::class)]
final class PaginatedFlagPostData extends Data
{
    /** @param  FlagPostData[]  $data */
    public function __construct(
        public array $data,
        public int $page,
        public int $perPage,
        public int $total,
    ) {}

    /**
     * @param  LengthAwarePaginator<int, \Modules\Detection\Models\Flag>  $paginator
     * @param  array<string, int>  $viewCounts  live view totals keyed by public_id (falls back to the DB column)
     */
    public static function fromPaginator(LengthAwarePaginator $paginator, array $viewCounts = []): self
    {
        return new self(
            data: array_map(
                static fn ($flag): FlagPostData => FlagPostData::fromModel(
                    $flag,
                    $viewCounts[$flag->public_id] ?? (int) $flag->view_count,
                ),
                $paginator->items(),
            ),
            page: $paginator->currentPage(),
            perPage: $paginator->perPage(),
            total: $paginator->total(),
        );
    }
}
