<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Detection\Enums\ApprovalStatus;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('flags', function (Blueprint $table): void {
            $table->unsignedInteger('status')->default(ApprovalStatus::Pending->value)->index()->after('severity');
            $table->string('title')->nullable()->after('subject_label');
            $table->string('category')->nullable()->after('title');
            $table->string('series_key')->nullable()->after('category');
            $table->jsonb('tags')->nullable()->after('series_key');
            $table->timestamp('published_at')->nullable()->index()->after('detected_at');
        });
    }

    public function down(): void
    {
        Schema::table('flags', function (Blueprint $table): void {
            $table->dropColumn(['status', 'title', 'category', 'series_key', 'tags', 'published_at']);
        });
    }
};
