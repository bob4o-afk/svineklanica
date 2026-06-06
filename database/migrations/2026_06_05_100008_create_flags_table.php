<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// A red-flag CLAIM raised by a detector (CLAUDE.md §1.1, data-sources.md §4).
// Polymorphic subject (tender / company / authority). `source_urls` is
// NON-NEGOTIABLE — no source → no flag → disinformation = disqualifying.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flags', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->unsignedInteger('type')->index();     // FlagType
            // The core hierarchy (CLAUDE.md §1.0): Sphere → CorruptionCategory → severity band.
            $table->unsignedInteger('sphere')->nullable()->index();   // Sphere (nullable: ingest may not infer it)
            $table->unsignedInteger('category')->nullable()->index(); // CorruptionCategory
            $table->unsignedTinyInteger('score');          // 0–100 suspicion % computed by the detector
            $table->unsignedInteger('severity')->index(); // FlagSeverity band, derived from score
            $table->morphs('subject');                     // subject_type + subject_id (internal id)
            $table->string('subject_label')->nullable();   // denormalized for the feed
            $table->text('explanation_bg');
            $table->jsonb('source_urls');                  // primary-source links behind the claim
            $table->jsonb('evidence')->nullable();         // the numbers behind it
            $table->timestamp('detected_at')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flags');
    }
};
