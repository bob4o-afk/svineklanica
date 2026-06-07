<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Adds a name embedding to contracting_authorities so the citizen search box can
// return semantically close authorities too (CLAUDE.md §1.2). Mirrors
// 2026_06_05_100009_add_vector_columns; dimension/index/ops from config/vector.php
// and MUST match the embedding model used to fill it (Google, via `search:embed`).
return new class extends Migration
{
    public function up(): void
    {
        $dim = (int) config('vector.dimensions', 384);
        $method = $this->safeIdent((string) config('vector.index', 'hnsw'));
        $ops = $this->safeIdent((string) config('vector.ops', 'vector_cosine_ops'));

        DB::statement("ALTER TABLE contracting_authorities ADD COLUMN name_embedding vector({$dim})");
        DB::statement(
            'CREATE INDEX contracting_authorities_name_embedding_idx ON contracting_authorities '.
            "USING {$method} (name_embedding {$ops})"
        );
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS contracting_authorities_name_embedding_idx');
        DB::statement('ALTER TABLE contracting_authorities DROP COLUMN IF EXISTS name_embedding');
    }

    /** Whitelist identifier chars — these come from config, never user input. */
    private function safeIdent(string $value): string
    {
        return preg_replace('/[^a-z0-9_]/i', '', $value) ?: 'hnsw';
    }
};
