<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Backfills the core-model columns the Flag model already expects (CLAUDE.md §1.0):
 * Sphere → CorruptionCategory → score. The original create_flags migration was edited
 * to include these AFTER it had already run on existing databases, so this corrective
 * migration adds them where missing. Guarded with hasColumn so a fresh DB (whose
 * create migration already defines them) is a no-op.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('flags', function (Blueprint $table): void {
            if (! Schema::hasColumn('flags', 'sphere')) {
                $table->unsignedInteger('sphere')->nullable()->index()->after('type');
            }
            if (! Schema::hasColumn('flags', 'category')) {
                $table->unsignedInteger('category')->nullable()->index()->after('sphere');
            }
            if (! Schema::hasColumn('flags', 'score')) {
                $table->unsignedTinyInteger('score')->default(0)->after('category');
            }
        });
    }

    public function down(): void
    {
        // Leave the columns in place on rollback — they are part of the intended schema.
    }
};
