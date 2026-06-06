<?php

declare(strict_types=1);

namespace Modules\Publishing\Data;

use App\Shared\Enums\CorruptionCategory;
use App\Shared\Enums\FlagSeverity;
use App\Shared\Enums\Sphere;
use Modules\Publishing\Enums\PostStatus;
use Modules\Publishing\Enums\PostTag;
use Modules\Publishing\Models\Post;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\LiteralTypeScriptType;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** API shape of a post — exposes public_id, never the internal id (backend.md §7). */
#[TypeScript]
final class PostData extends Data
{
    /**
     * @param  array<int, int>  $tags  PostTag values — the punk badges (CLAUDE.md §1.0.1)
     * @param  array<int, string>  $sourceUrls
     */
    public function __construct(
        public string $publicId,
        public string $title,
        public string $slug,
        public ?string $excerpt,
        public string $body,
        public PostStatus $status,
        public ?Sphere $sphere,
        public ?CorruptionCategory $category,
        public ?FlagSeverity $severity,
        #[LiteralTypeScriptType('number[]')]
        public array $tags,
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
            sphere: $post->sphere,
            category: $post->category,
            severity: $post->severity,
            tags: collect($post->tags ?? [])->map(fn (PostTag $tag): int => $tag->value)->all(),
            viewCount: (int) $post->view_count,
            authorName: $post->author?->name,
            sourceUrls: $post->source_urls ?? [],
            publishedAt: $post->published_at?->toIso8601String(),
        );
    }
}
