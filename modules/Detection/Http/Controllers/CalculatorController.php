<?php

declare(strict_types=1);

namespace Modules\Detection\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\Detection\Data\CalculateCorruptionTaxData;
use Modules\Detection\Services\CorruptionTaxCalculator;

/**
 * The corruption-tax calculator endpoint (CLAUDE.md). Thin (backend.md §2): validate
 * via the input DTO, call the service, return the typed result. Public, read-only,
 * rate-limited (security.md §1/§2) — computed live from ingested data on each press.
 * Read-only computation → 200 (not 201); the response shape is {@see CorruptionTaxData}.
 */
final class CalculatorController
{
    public function __construct(private readonly CorruptionTaxCalculator $calculator) {}

    public function corruptionTax(CalculateCorruptionTaxData $data): JsonResponse
    {
        return response()->json($this->calculator->calculate($data->taxesPaid));
    }
}
