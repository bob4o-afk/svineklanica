<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Company (bidder / contractor). Unified on EIK (БУЛСТАТ), the natural key for
// upsert + shell/serial-winner clustering (data-sources.md §3). name_embedding
// (vector) is added in the add_vector_columns migration.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->string('eik')->nullable()->unique(); // natural key (nullable: some sources omit it)
            $table->string('name');
            $table->string('address')->nullable();
            $table->string('owner_name')->nullable();
            $table->string('phone')->nullable();
            $table->string('source_url')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
