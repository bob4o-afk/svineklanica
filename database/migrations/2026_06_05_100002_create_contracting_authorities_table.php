<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Contracting authority (възложител) — the public body that opens a tender.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contracting_authorities', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->string('name');
            $table->string('eik')->nullable()->index(); // ЕИК/БУЛСТАT, where known
            $table->string('region')->nullable();
            $table->string('source_url')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contracting_authorities');
    }
};
