<?php

declare(strict_types=1);

namespace App\Support\PublicId;

use Illuminate\Database\Eloquent\Model;

/**
 * Assigns a UUIDv7 `public_id` on create and makes route-model binding resolve
 * on it. The auto-increment `id` stays internal and is never serialized
 * (backend.md §7).
 */
trait HasPublicId
{
    protected static function bootHasPublicId(): void
    {
        static::creating(function (Model $model): void {
            if (empty($model->public_id)) {
                $model->public_id = PublicIdGenerator::generate();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }
}
