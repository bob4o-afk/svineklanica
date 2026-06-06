<?php

declare(strict_types=1);

namespace Modules\Detection\Http\Controllers;

use Illuminate\Http\JsonResponse;

/** Stubs for map/search endpoints — wired, empty until ingest + geo aggregate land. */
final class ReadModelStubController
{
    public function regions(): JsonResponse
    {
        return response()->json([]);
    }

    public function search(): JsonResponse
    {
        return response()->json([
            'authorities' => [],
            'companies' => [],
            'tenders' => [],
        ]);
    }

    public function priceSeries(string $key): JsonResponse
    {
        abort(404);
    }

    public function serialWinnerGraph(string $publicId): JsonResponse
    {
        return response()->json(['nodes' => [], 'edges' => []]);
    }
}
