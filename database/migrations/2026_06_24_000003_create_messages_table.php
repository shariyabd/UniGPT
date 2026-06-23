<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Direct messages within a conversation. Text-only for v1 (file sharing is
 * intentionally out of scope). The DB is the source of truth — realtime
 * (Pusher/Ably) is best-effort delivery layered on top, so every message is
 * persisted here first and broadcast second.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')
                ->constrained()
                ->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('sender_id')
                ->constrained('users')
                ->cascadeOnUpdate()->cascadeOnDelete();
            $table->text('body');
            $table->timestamps();

            // Paginated history + "messages newer than id" polling both read by
            // (conversation, id) — id ordering doubles as chronological order.
            $table->index(['conversation_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
