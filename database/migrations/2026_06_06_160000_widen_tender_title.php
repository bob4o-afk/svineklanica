<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * TED (and other) tender titles routinely exceed varchar(255) — they embed the
 * full project name + funding programme. Widen `title` to TEXT so real records
 * ingest instead of being skipped with a "value too long" error.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenders', function (Blueprint $table): void {
            $table->text('title')->change();
        });
    }

    public function down(): void
    {
        Schema::table('tenders', function (Blueprint $table): void {
            $table->string('title')->change();
        });
    }
};
