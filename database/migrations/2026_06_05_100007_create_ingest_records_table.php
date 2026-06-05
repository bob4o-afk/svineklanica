<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Landing table for the scraper's NDJSON ingest contract (scraping.md §2).
// Stores the normalized payload + provenance so we can re-derive domain rows
// without re-fetching. Internal/staging — exempt from public_id (backend.md §7).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ingest_records', function (Blueprint $table) {
            $table->id();
            $table->string('source');
            $table->string('natural_key');
            $table->string('source_url');
            $table->timestamp('fetched_at');
            $table->unsignedInteger('schema_version')->default(1);
            $table->jsonb('payload');
            $table->string('raw_hash')->nullable();
            $table->string('status')->default('ingested'); // ingested | skipped
            $table->text('skip_reason')->nullable();
            $table->timestamp('ingested_at')->nullable();
            $table->timestamps();

            $table->unique(['source', 'natural_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ingest_records');
    }
};
