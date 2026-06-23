<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Direct (1:1) conversations between two users — distinct from the AI
 * `chat_sessions` table, which is single-user → assistant. A conversation owns
 * many `messages` and has exactly two participants (see conversation_user).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            // Denormalised for cheap inbox sorting ("most recent chat first")
            // without aggregating the messages table on every list render.
            $table->timestamp('last_message_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
