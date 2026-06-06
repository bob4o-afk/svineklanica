<?php

declare(strict_types=1);

namespace Modules\Detection\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Detection\Contracts\FlagRepository;
use Modules\Detection\Data\FlagPostData;
use Modules\Detection\Data\ReviewDecisionData;
use Spatie\LaravelData\PaginatedDataCollection;

/** Admin review queue for flag-posts (API_SEAM.md). */
final class FlagPostController
{
    public function __construct(private readonly FlagRepository $flags) {}

    /** @return PaginatedDataCollection<int, FlagPostData> */
    public function index(Request $request): PaginatedDataCollection
    {
        $perPage = min(50, max(1, (int) $request->integer('per_page', 20)));
        $status = (string) $request->query('status', 'pending');

        return FlagPostData::collect(
            $this->flags->paginateByStatus($status, $perPage),
            PaginatedDataCollection::class,
        );
    }

    public function show(string $publicId): FlagPostData
    {
        $flag = $this->flags->findAny($publicId);
        abort_if($flag === null, 404);

        return FlagPostData::fromModel($flag);
    }

    public function approve(string $publicId, ReviewDecisionData $decision): FlagPostData
    {
        $flag = $this->flags->findAny($publicId);
        abort_if($flag === null, 404);

        return FlagPostData::fromModel($this->flags->approve($flag, $decision));
    }

    public function reject(string $publicId, Request $request): FlagPostData
    {
        $flag = $this->flags->findAny($publicId);
        abort_if($flag === null, 404);

        $note = $request->input('note');

        return FlagPostData::fromModel($this->flags->reject($flag, is_string($note) ? $note : null));
    }
}
