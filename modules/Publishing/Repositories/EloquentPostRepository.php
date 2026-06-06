<?php

declare(strict_types=1);

namespace Modules\Publishing\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Publishing\Contracts\PostRepository;
use Modules\Publishing\Data\PostFilterData;
use Modules\Publishing\Models\Post;

final class EloquentPostRepository implements PostRepository
{
    public function paginatePublished(int $perPage = 15): LengthAwarePaginator
    {
        return Post::query()
            ->published()
            ->with('author')
            ->orderByDesc('published_at')
            ->paginate($perPage);
    }

    public function paginatePublishedFiltered(PostFilterData $filter): LengthAwarePaginator
    {
        return Post::query()
            ->published()
            ->with('author')
            ->when($filter->sphere !== null, fn ($q) => $q->where('sphere', $filter->sphere))
            ->when($filter->category !== null, fn ($q) => $q->where('category', $filter->category))
            ->when($filter->severity !== null, fn ($q) => $q->where('severity', $filter->severity))
            // tags is a jsonb array of PostTag ints — match posts carrying the tag.
            ->when($filter->tag !== null, fn ($q) => $q->whereJsonContains('tags', $filter->tag->value))
            ->orderByDesc('published_at')
            ->paginate($filter->perPage);
    }

    public function findPublished(string $publicId): ?Post
    {
        return Post::query()->published()->with('author')->where('public_id', $publicId)->first();
    }

    public function find(string $publicId): ?Post
    {
        return Post::query()->with('author')->where('public_id', $publicId)->first();
    }

    public function create(array $attributes): Post
    {
        return Post::create($attributes);
    }

    public function update(Post $post, array $attributes): Post
    {
        $post->update($attributes);

        return $post->refresh();
    }

    public function delete(Post $post): void
    {
        $post->delete();
    }
}
