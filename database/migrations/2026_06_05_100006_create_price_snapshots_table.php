<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Point-in-time price captures so the price-over-time graph is REAL, not
// reconstructed (data-sources.md §2). `product_key` is the normalized cluster
// key shared across spellings of the same item.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('price_snapshots', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('tender_item_id')->nullable()
                ->constrained('tender_items')->nullOnDelete();
            $table->string('product_key')->index();
            $table->string('description');
            $table->decimal('price', 18, 2);
            $table->string('currency', 3);
            $table->timestamp('captured_at')->index();
            $table->string('source_url');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_snapshots');
    }
};
