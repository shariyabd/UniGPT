<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Earned achievements/badges. Definitions live in code (the Achievement enum);
 * this table only records which user earned which badge and when, so awards are
 * permanent even if a definition's thresholds later change.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_achievements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // The Achievement enum case value (e.g. "streak_7"). Not an FK — the
            // catalogue is code-defined.
            $table->string('achievement');
            $table->timestamp('earned_at');
            $table->timestamps();

            // A badge is earned at most once per user.
            $table->unique(['user_id', 'achievement']);
            $table->index('achievement');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_achievements');
    }
};
