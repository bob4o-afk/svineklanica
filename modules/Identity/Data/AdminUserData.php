<?php

declare(strict_types=1);

namespace Modules\Identity\Data;

use App\Models\User;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** Admin session shape for the SPA (contract.ts AdminUser, snake_case JSON). */
#[TypeScript]
#[MapOutputName(SnakeCaseMapper::class)]
final class AdminUserData extends Data
{
    public function __construct(
        public string $public_id,
        public string $name,
        public string $email,
        /** @var 'admin'|'reviewer' */
        public string $role,
    ) {}

    public static function fromModel(User $user): self
    {
        return new self(
            public_id: $user->public_id,
            name: $user->name,
            email: $user->email,
            role: $user->isAdmin() ? 'admin' : 'reviewer',
        );
    }
}
