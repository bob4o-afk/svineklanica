<?php

declare(strict_types=1);

namespace Modules\Publishing\Data;

use Modules\Publishing\Enums\PostStatus;
use Modules\Publishing\Models\Post;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\LiteralTypeScriptType;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** API shape of a post — exposes public_id, never the internal id (backend.md §7). */
#[TypeScript]
final class PostData extends Data
{
    /** @param array<int, string> $sourceUrls */
    public function __construct(
        public string $publicId,
        public string $title,
        public string $slug,
        public ?string $excerpt,
        public string $body,
        public PostStatus $status,
        public int $viewCount,
        public ?string $authorName,
        #[LiteralTypeScriptType('string[]')]
        public array $sourceUrls,
        public ?string $publishedAt,
    ) {}

    public static function fromModel(Post $post): self
    {
        return new self(
            publicId: $post->public_id,
            title: $post->title,
            slug: $post->slug,
            excerpt: $post->excerpt,
            body: $post->body,
            status: $post->status,
            viewCount: (int) $post->view_count,
            authorName: $post->author?->name,
            sourceUrls: $post->source_urls ?? [],
            publishedAt: $post->published_at?->toIso8601String(),
        );
    }
}
