<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Conversation participants. A 1:1 conversation has exactly two rows here. The
 * (conversation_id, user_id) unique key prevents a user being added twice; the
 * user_id index powers the "my conversations" inbox lookup.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversation_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')
                ->constrained()
                ->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnUpdate()->cascadeOnDelete();
            // Per-participant read cursor: the last message id this user has seen.
            // Drives unread badges without a per-message read table.
            $table->unsignedBigInteger('last_read_message_id')->nullable();
            $table->timestamps();

            $table->unique(['conversation_id', 'user_id']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversation_user');
    }
};
