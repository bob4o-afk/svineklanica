<?php

declare(strict_types=1);

namespace Modules\Publishing\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Publishing\Contracts\PostRepository;
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
