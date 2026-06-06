<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Presentation-facing fields on a flag (CLAUDE.md §1.2). The first two are display,
 * the last two are denormalized read-keys so the public feed + region map filter in
 * SQL (with pagination intact) instead of post-filtering in PHP:
 *  - `title`: optional punchy headline for the post card; falls back to the type label.
 *  - `series_key`: links a price_discrepancy flag to its price-over-time series (product_key).
 *  - `sector`: CPV-derived procurement sector (ProcurementSector) — the citizen filter.
 *  - `region_code`: NUTS3 oblast of the subject's authority — what the map colours by.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('flags', function (Blueprint $table): void {
            $table->string('title')->nullable()->after('severity');
            $table->string('series_key')->nullable()->index()->after('title');
            $table->string('sector')->nullable()->index()->after('series_key');
            $table->string('region_code')->nullable()->index()->after('sector');
        });
    }

    public function down(): void
    {
        Schema::table('flags', function (Blueprint $table): void {
            $table->dropColumn(['title', 'series_key', 'sector', 'region_code']);
        });
    }
};
