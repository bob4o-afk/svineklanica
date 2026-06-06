<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A flag's `title` and `subject_label` are derived from the tender title, which for
 * real TED notices exceeds varchar(255). Widen both to TEXT so AI/detector flags on
 * long-titled tenders persist instead of failing with "value too long".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('flags', function (Blueprint $table): void {
            $table->text('title')->nullable()->change();
            $table->text('subject_label')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('flags', function (Blueprint $table): void {
            $table->string('title')->nullable()->change();
            $table->string('subject_label')->nullable()->change();
        });
    }
};
