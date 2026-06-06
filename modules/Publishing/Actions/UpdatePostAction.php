<?php

declare(strict_types=1);

namespace Modules\Publishing\Actions;

use Modules\Publishing\Contracts\PostRepository;
use Modules\Publishing\Data\UpdatePostData;
use Modules\Publishing\Enums\PostStatus;
use Modules\Publishing\Enums\PostTag;
use Modules\Publishing\Models\Post;

final class UpdatePostAction
{
    public function __construct(private readonly PostRepository $posts) {}

    public function execute(Post $post, UpdatePostData $data): Post
    {
        // Stamp published_at the first time it goes live; keep it on later edits.
        $publishedAt = $data->status === PostStatus::Published
            ? ($post->published_at ?? now())
            : $post->published_at;

        return $this->posts->update($post, [
            'title' => $data->title,
            'excerpt' => $data->excerpt,
            'body' => $data->body,
            'status' => $data->status,
            'sphere' => $data->sphere,
            'category' => $data->category,
            'severity' => $data->severity,
            'tags' => PostTag::collectFrom($data->tags),
            'source_urls' => $data->sourceUrls ?? [],
            'published_at' => $publishedAt,
        ]);
    }
}
