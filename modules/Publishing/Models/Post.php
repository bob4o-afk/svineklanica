<?php

declare(strict_types=1);

namespace Modules\Publishing\Models;

use App\Models\User;
use App\Support\PublicId\HasPublicId;
use Database\Factories\PostFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Publishing\Enums\PostStatus;

/**
 * A corruption post in the public feed (backend.md §14). `view_count` is the
 * durable mirror of the Redis per-IP counter — never incremented per request
 * here.
 */
final class Post extends Model
{
    use HasFactory;
    use HasPublicId;
    use SoftDeletes;

    protected $fillable = [
        'author_id',
        'title',
        'slug',
        'excerpt',
        'body',
        'status',
        'source_urls',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => PostStatus::class,
            'source_urls' => 'array',
            'view_count' => 'integer',
            'published_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, Post> */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /** Only posts live in the public feed. @param Builder<Post> $query */
    public function scopePublished(Builder $query): void
    {
        $query->where('status', PostStatus::Published);
    }

    protected static function newFactory(): Factory
    {
        return PostFactory::new();
    }
}
