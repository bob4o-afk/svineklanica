<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Core hierarchy (CLAUDE.md §1.0) on tenders — tagged at ingest from the
// authority / CPV / source (data-sources.md §3). Detectors copy these onto the
// flags they raise, so the citizen feed is filterable by Sphere → Category.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenders', function (Blueprint $table) {
            $table->unsignedInteger('sphere')->nullable()->index()->after('cpv_code');   // Sphere
            $table->unsignedInteger('category')->nullable()->index()->after('sphere');   // CorruptionCategory
        });
    }

    public function down(): void
    {
        Schema::table('tenders', function (Blueprint $table) {
            $table->dropColumn(['sphere', 'category']);
        });
    }
};
