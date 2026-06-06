<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Newsletter subscribers — citizens who opt in to get notified about new
// corruption posts / alerts (backend.md §1 Notifications context). Public opt-in,
// one-click unsubscribe via a token. No PII beyond the e-mail they gave us.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscribers', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->string('email')->unique();
            $table->string('unsubscribe_token', 64)->unique();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('unsubscribed_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscribers');
    }
};
