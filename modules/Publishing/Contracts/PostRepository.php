<?php

declare(strict_types=1);

namespace Modules\Publishing\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Publishing\Models\Post;

/** The only place that touches the posts table (backend.md §2). */
interface PostRepository
{
    /** @return LengthAwarePaginator<Post> Published posts, newest first. */
    public function paginatePublished(int $perPage = 15): LengthAwarePaginator;

    /** Published only — for the public feed. */
    public function findPublished(string $publicId): ?Post;

    /** Any status — for admin management. */
    public function find(string $publicId): ?Post;

    /** @param array<string, mixed> $attributes */
    public function create(array $attributes): Post;

    /** @param array<string, mixed> $attributes */
    public function update(Post $post, array $attributes): Post;

    public function delete(Post $post): void;
}
