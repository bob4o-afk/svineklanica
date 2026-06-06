<?php

declare(strict_types=1);

namespace Modules\Presentation\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Presentation\Contracts\PresentationRepository;
use Modules\Presentation\Data\FlagFeedFilterData;
use Modules\Presentation\Data\FlagPostData;
use Modules\Presentation\Data\PaginatedFlagPostData;
use Modules\Presentation\Services\FlagViewService;

/**
 * Public, read-only flag-post feed — the citizen entry point (contract `/flag-posts`).
 * Filterable by type / sector / severity / region / text; sorted newest or by severity.
 * The route key is the flag's `public_id` (a UUIDv7), never the internal id (backend.md §7).
 */
final class FlagPostController
{
    public function __construct(
        private readonly PresentationRepository $repo,
        private readonly FlagViewService $views,
    ) {}

    public function index(Request $request): PaginatedFlagPostData
    {
        $filter = FlagFeedFilterData::fromRequest($request);
        $paginator = $this->repo->paginateFlags($filter);

        // One Redis round-trip for the whole page's live view totals.
        return PaginatedFlagPostData::fromPaginator($paginator, $this->views->totals($paginator->items()));
    }

    public function show(Request $request, string $publicId): FlagPostData
    {
        $model = $this->repo->findFlag($publicId);
        abort_if($model === null, 404);

        // Opening the post IS the view: record it (deduped by IP, backend.md §14) and
        // reflect the fresh total in the same response.
        $this->views->record($model->public_id, $request->ip());

        return FlagPostData::fromModel($model, $this->views->total($model));
    }
}
