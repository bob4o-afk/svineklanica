<?php

declare(strict_types=1);

namespace Modules\Detection\Http\Controllers;

use Modules\Detection\Contracts\FlagRepository;
use Modules\Detection\Data\FlagData;
use Modules\Detection\Data\FlagFilterData;
use Spatie\LaravelData\PaginatedDataCollection;

/** Public, read-only flag feed filtered by Sphere → Category → Severity (CLAUDE.md §1.0). */
final class FlagController
{
    public function __construct(private readonly FlagRepository $flags) {}

    /** @return PaginatedDataCollection<int, FlagData> */
    public function index(FlagFilterData $filter): PaginatedDataCollection
    {
        return FlagData::collect(
            $this->flags->paginateFiltered($filter),
            PaginatedDataCollection::class,
        );
    }

    public function show(string $flag): FlagData
    {
        $model = $this->flags->find($flag);
        abort_if($model === null, 404);

        return FlagData::fromModel($model);
    }
}
