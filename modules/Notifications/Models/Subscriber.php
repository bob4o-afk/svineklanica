<?php

declare(strict_types=1);

namespace Modules\Notifications\Models;

use App\Support\PublicId\HasPublicId;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * A citizen who opted in to notifications. `unsubscribed_at` is a soft flag so a
 * re-subscribe just clears it (no duplicate rows on the unique e-mail).
 */
final class Subscriber extends Model
{
    use HasPublicId;

    protected $fillable = [
        'email',
        'unsubscribe_token',
        'confirmed_at',
        'unsubscribed_at',
    ];

    protected function casts(): array
    {
        return [
            'confirmed_at' => 'datetime',
            'unsubscribed_at' => 'datetime',
        ];
    }

    /** Active = still subscribed. @param Builder<Subscriber> $query */
    public function scopeActive(Builder $query): void
    {
        $query->whereNull('unsubscribed_at');
    }
}
