<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Publishing\Enums\PostStatus;

// Posts — the public corruption feed (backend.md §14). Authored by admins,
// read by citizens (no login). `view_count` is the durable total flushed from
// the Redis per-IP counter.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('excerpt')->nullable();
            $table->longText('body');
            $table->unsignedInteger('status')->default(PostStatus::Draft->value)->index();
            // The core hierarchy (CLAUDE.md §1.0) — editorial, set by the admin on publish,
            // so the feed is filterable by Sphere → Category → Severity (nullable until tagged).
            $table->unsignedInteger('sphere')->nullable()->index();   // Sphere
            $table->unsignedInteger('category')->nullable()->index(); // CorruptionCategory
            $table->unsignedInteger('severity')->nullable()->index(); // FlagSeverity band
            // Punk tags / badges (CLAUDE.md §1.0.1) — array of PostTag ints. jsonb so we can
            // filter "posts carrying tag X" with a containment query.
            $table->jsonb('tags')->nullable();
            $table->jsonb('source_urls')->nullable(); // primary-source links behind the claims
            $table->unsignedBigInteger('view_count')->default(0);
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
