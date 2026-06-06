<?php

declare(strict_types=1);

namespace Modules\Detection\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Detection\Data\FlagFilterData;
use Modules\Detection\Enums\FlagType;
use Modules\Detection\Models\Flag;

/** The only place that touches the flags table (backend.md §2). */
interface FlagRepository
{
    /** @return LengthAwarePaginator<Flag> Flags matching the filter, most suspicious + newest first. */
    public function paginateFiltered(FlagFilterData $filter): LengthAwarePaginator;

    public function find(string $publicId): ?Flag;

    /** Clear every flag of a given type — lets a detector re-run idempotently. */
    public function deleteByType(FlagType $type): void;

    /**
     * Persist a batch of flag rows (one detector's output).
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return int number of flags written
     */
    public function createMany(array $rows): int;
}
