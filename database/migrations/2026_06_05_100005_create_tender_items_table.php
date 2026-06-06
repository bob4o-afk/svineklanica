<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Line items of a tender — the unit of the price-discrepancy detector
// (CLAUDE.md §1.1.1). Free-text Bulgarian descriptions get clustered by
// vector similarity; description_embedding is added later.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tender_items', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('tender_id')->constrained('tenders')->cascadeOnDelete();
            $table->text('description');
            $table->decimal('quantity', 18, 3)->nullable();
            $table->string('unit')->nullable();
            $table->decimal('unit_price', 18, 2)->nullable();
            $table->string('currency', 3)->nullable();
            $table->boolean('vat_included')->nullable();
            $table->string('source_url')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tender_items');
    }
};
