<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Procurement\Enums\TenderStatus;

// A public procurement (обществена поръчка). Idempotent upsert on
// (source, natural_key) — scraping.md §4. description_embedding (vector) is
// added in the add_vector_columns migration.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenders', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();

            // Provenance / idempotency (every record is sourced — data-sources.md §0).
            $table->string('source');
            $table->string('natural_key');
            $table->string('source_url');
            $table->timestamp('fetched_at');

            $table->foreignId('contracting_authority_id')->nullable()
                ->constrained('contracting_authorities')->nullOnDelete();
            $table->foreignId('winner_company_id')->nullable()
                ->constrained('companies')->nullOnDelete();

            $table->string('title');
            $table->text('description')->nullable();
            $table->string('cpv_code')->nullable()->index(); // CPV category (reliable key)

            $table->decimal('value', 18, 2)->nullable();
            $table->string('currency', 3)->nullable();
            $table->boolean('vat_included')->nullable();

            $table->unsignedInteger('status')->default(TenderStatus::Announced->value)->index();

            $table->date('announced_at')->nullable();
            $table->date('deadline_at')->nullable();
            $table->date('awarded_at')->nullable();
            $table->date('cancelled_at')->nullable();

            $table->timestamps();

            $table->unique(['source', 'natural_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenders');
    }
};
