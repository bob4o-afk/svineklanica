<?php

declare(strict_types=1);

namespace Modules\Publishing\Http\Controllers;

use Modules\Publishing\Contracts\PostRepository;
use Modules\Publishing\Data\PostData;
use Modules\Publishing\Data\PostFilterData;
use Spatie\LaravelData\PaginatedDataCollection;

/** Public, read-only corruption feed (no auth; rate-limited at the route). */
final class PublicPostController
{
    public function __construct(private readonly PostRepository $posts) {}

    /** @return PaginatedDataCollection<int, PostData> */
    public function index(PostFilterData $filter): PaginatedDataCollection
    {
        return PostData::collect(
            $this->posts->paginatePublishedFiltered($filter),
            PaginatedDataCollection::class,
        );
    }

    public function show(string $post): PostData
    {
        $model = $this->posts->findPublished($post);
        abort_if($model === null, 404);

        return PostData::fromModel($model);
    }
}
