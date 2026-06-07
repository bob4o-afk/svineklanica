<?php

declare(strict_types=1);

namespace App\Support\Vector;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Casts a pgvector column <-> a PHP list<float>. Postgres returns a vector as a
 * bracketed string ("[0.1,0.2,...]") and accepts the same form on write, so this
 * cast parses it on read and renders it on write. The column type validates the
 * value — we never build SQL from it (security.md §6).
 *
 * @implements CastsAttributes<list<float>|null, list<float>|array<int, float>|string|null>
 */
final class VectorCast implements CastsAttributes
{
    /** @return list<float>|null */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?array
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_array($value)) {
            return array_map(static fn ($v): float => (float) $v, array_values($value));
        }

        $decoded = json_decode((string) $value, true);
        if (! is_array($decoded)) {
            return null;
        }

        return array_map(static fn ($v): float => (float) $v, array_values($decoded));
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            return $value;
        }

        if (! is_array($value)) {
            return null;
        }

        return '['.implode(',', array_map(static fn ($v): string => (string) (float) $v, $value)).']';
    }
}
