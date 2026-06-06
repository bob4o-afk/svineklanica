<?php

declare(strict_types=1);

use App\Shared\Enums\CorruptionCategory;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// A budget payment (нерегламентирани плащания) — a public spender paying a
// recipient, ingested from СЕБРА (data-sources.md). Its own table (not the
// tenders table) because a payment is a distinct record_type (contract.py v2)
// and because the "corruption tax calculator" needs to SUM(amount) over flagged
// vs all payments — sourced + auditable. Idempotent upsert on (source, natural_key).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();

            // Provenance / idempotency (every record is sourced — data-sources.md §0).
            $table->string('source');
            $table->string('natural_key');
            $table->string('source_url');
            $table->timestamp('fetched_at');

            // Spender = the paying authority; recipient = the company paid.
            $table->foreignId('spender_authority_id')->nullable()
                ->constrained('contracting_authorities')->nullOnDelete();
            $table->foreignId('recipient_company_id')->nullable()
                ->constrained('companies')->nullOnDelete();

            $table->string('title');
            $table->text('description')->nullable();

            $table->unsignedInteger('sphere')->nullable()->index();   // Sphere
            $table->unsignedInteger('category')
                ->default(CorruptionCategory::UnregulatedPayment->value)->index(); // CorruptionCategory

            $table->decimal('amount', 18, 2)->nullable();
            $table->string('currency', 3)->nullable();
            $table->date('paid_at')->nullable()->index();

            $table->timestamps();

            $table->unique(['source', 'natural_key']);
            // Calculator aggregation: SUM(amount) sliced by sphere/category.
            $table->index(['sphere', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
