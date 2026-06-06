<?php

declare(strict_types=1);

namespace Modules\Notifications\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** Public opt-in input — anyone may subscribe; validated + normalized here (security.md §5). */
#[TypeScript]
final class SubscribeData extends Data
{
    public function __construct(
        public string $email,
    ) {}

    /** @return array<string, mixed> */
    public static function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email:rfc', 'max:255'],
        ];
    }

    /** Normalize so the unique-email index dedupes case/whitespace variants. */
    public static function prepareForPipeline(array $properties): array
    {
        if (isset($properties['email']) && is_string($properties['email'])) {
            $properties['email'] = mb_strtolower(trim($properties['email']));
        }

        return $properties;
    }

    public static function authorize(): bool
    {
        return true; // public endpoint; abuse is handled by the route throttle
    }
}
