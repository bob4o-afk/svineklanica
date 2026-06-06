<?php

declare(strict_types=1);

namespace Modules\Detection\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * Input for the corruption-tax calculator: how much tax the citizen paid. Validated
 * + authorized here (backend.md §4/§6). Public, read-only computation — any caller
 * may run it; the route rate-limits + abuse-guards (security.md §1/§2).
 */
#[TypeScript]
final class CalculateCorruptionTaxData extends Data
{
    public function __construct(
        public float $taxesPaid = 0.0,
    ) {}

    /** @return array<string, mixed> */
    public static function rules(): array
    {
        return [
            // A citizen's annual tax bill — bounded to keep the projection sane.
            'taxesPaid' => ['required', 'numeric', 'min:0', 'max:100000000'],
        ];
    }

    public static function authorize(): bool
    {
        return true; // public, read-only computation
    }
}
