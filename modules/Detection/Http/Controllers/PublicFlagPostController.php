<?php

declare(strict_types=1);

namespace Modules\Detection\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Detection\Contracts\FlagRepository;
use Modules\Detection\Data\FlagPostData;
use Spatie\LaravelData\PaginatedDataCollection;

/** Public corruption feed — approved flags only (API_SEAM.md). */
final class PublicFlagPostController
{
    public function __construct(private readonly FlagRepository $flags) {}

    /** @return PaginatedDataCollection<int, FlagPostData> */
    public function index(Request $request): PaginatedDataCollection
    {
        $perPage = min(50, max(1, (int) $request->integer('per_page', 6)));

        $paginator = $this->flags->paginateApproved([
            'type' => $request->query('type', []),
            'severity' => $request->query('severity', []),
            'category' => $request->query('category', []),
            'region' => $request->query('region'),
            'q' => $request->query('q'),
            'sort' => $request->query('sort', 'newest'),
        ], $perPage);

        return FlagPostData::collect($paginator, PaginatedDataCollection::class);
    }

    public function show(string $publicId): FlagPostData
    {
        $flag = $this->flags->findApproved($publicId);
        abort_if($flag === null, 404);

        return FlagPostData::fromModel($flag);
    }
}
