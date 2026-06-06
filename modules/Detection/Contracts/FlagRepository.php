<?php

declare(strict_types=1);

namespace Modules\Detection\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Detection\Data\FlagFilterData;
use Modules\Detection\Models\Flag;

/** The only place that touches the flags table (backend.md §2). */
interface FlagRepository
{
    /** @return LengthAwarePaginator<Flag> Flags matching the filter, most suspicious + newest first. */
    public function paginateFiltered(FlagFilterData $filter): LengthAwarePaginator;

    public function find(string $publicId): ?Flag;
}
