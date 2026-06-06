<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Adds pgvector embedding columns + similarity indexes (backend.md §12).
// Embeddings are produced by the Python scraper (scraping.md §8); PHP only
// stores + indexes them. Dimension/index/ops come from config/vector.php and
// MUST match the scraper's embedding model.
return new class extends Migration
{
    /** @var array<int, array{table: string, column: string}> */
    private array $targets = [
        ['table' => 'companies', 'column' => 'name_embedding'],
        ['table' => 'tenders', 'column' => 'description_embedding'],
        ['table' => 'tender_items', 'column' => 'description_embedding'],
    ];

    public function up(): void
    {
        $dim = (int) config('vector.dimensions', 384);
        $method = $this->safeIdent((string) config('vector.index', 'hnsw'));
        $ops = $this->safeIdent((string) config('vector.ops', 'vector_cosine_ops'));

        foreach ($this->targets as $t) {
            DB::statement("ALTER TABLE {$t['table']} ADD COLUMN {$t['column']} vector({$dim})");
            DB::statement(
                "CREATE INDEX {$t['table']}_{$t['column']}_idx ON {$t['table']} ".
                "USING {$method} ({$t['column']} {$ops})"
            );
        }
    }

    public function down(): void
    {
        foreach ($this->targets as $t) {
            DB::statement("DROP INDEX IF EXISTS {$t['table']}_{$t['column']}_idx");
            DB::statement("ALTER TABLE {$t['table']} DROP COLUMN IF EXISTS {$t['column']}");
        }
    }

    /** Whitelist identifier chars — these come from config, never user input. */
    private function safeIdent(string $value): string
    {
        return preg_replace('/[^a-z0-9_]/i', '', $value) ?: 'hnsw';
    }
};
