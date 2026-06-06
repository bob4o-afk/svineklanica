<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Durable view counter for the citizen feed (backend.md §14). Redis is the hot counter
 * (deduped per IP); a scheduled flusher (flags:flush-views) folds the deltas into this
 * column so totals survive a Redis flush and stay queryable/sortable. Guarded with
 * hasColumn so a re-run / fresh DB is a no-op.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('flags', function (Blueprint $table): void {
            if (! Schema::hasColumn('flags', 'view_count')) {
                $table->unsignedBigInteger('view_count')->default(0)->after('detected_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('flags', function (Blueprint $table): void {
            $table->dropColumn('view_count');
        });
    }
};
