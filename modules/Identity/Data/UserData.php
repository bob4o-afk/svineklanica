<?php

declare(strict_types=1);

namespace Modules\Identity\Data;

use App\Models\User;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** Public-safe user shape — exposes public_id, never the internal id (backend.md §7). */
#[TypeScript]
final class UserData extends Data
{
    public function __construct(
        public string $publicId,
        public string $name,
        public string $email,
        public bool $isAdmin,
    ) {}

    public static function fromModel(User $user): self
    {
        return new self(
            publicId: $user->public_id,
            name: $user->name,
            email: $user->email,
            isAdmin: $user->isAdmin(),
        );
    }
}
