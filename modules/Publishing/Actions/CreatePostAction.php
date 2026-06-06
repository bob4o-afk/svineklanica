<?php

declare(strict_types=1);

namespace Modules\Publishing\Actions;

use App\Models\User;
use Illuminate\Support\Str;
use Modules\Publishing\Contracts\PostRepository;
use Modules\Publishing\Data\StorePostData;
use Modules\Publishing\Enums\PostStatus;
use Modules\Publishing\Models\Post;

/** Creates a post in Draft (pending) state, authored by the given admin. */
final class CreatePostAction
{
    public function __construct(private readonly PostRepository $posts) {}

    public function execute(StorePostData $data, User $author): Post
    {
        return $this->posts->create([
            'author_id' => $author->id,
            'title' => $data->title,
            'slug' => $this->slug($data->title),
            'excerpt' => $data->excerpt,
            'body' => $data->body,
            'source_urls' => $data->sourceUrls ?? [],
            'status' => PostStatus::Draft,
        ]);
    }

    /** Cyrillic-aware slug (Str::slug transliterates) + a short suffix for uniqueness. */
    private function slug(string $title): string
    {
        $base = Str::slug($title);
        $base = $base !== '' ? $base : 'post';

        return $base.'-'.Str::lower(Str::random(6));
    }
}
