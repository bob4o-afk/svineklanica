<?php

declare(strict_types=1);

namespace Modules\Identity\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** Login input — validated + sanitized at the boundary (backend.md §6). */
#[TypeScript]
final class LoginData extends Data
{
    public function __construct(
        public string $email,
        public string $password,
    ) {}

    /** @return array<string, array<int, string>> */
    public static function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
        ];
    }
}
