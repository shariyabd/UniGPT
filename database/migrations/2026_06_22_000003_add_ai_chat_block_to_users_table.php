<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds AI-chat access controls to users so admins can temporarily or permanently
 * block a user from the AI chat (e.g. for repeated irrelevant usage that wastes
 * API budget) with a reason note, and restore access later.
 *
 * A user is blocked when `ai_chat_blocked_at` is set and `ai_chat_blocked_until`
 * is either null (permanent) or still in the future (temporary).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->timestamp('ai_chat_blocked_at')->nullable()->after('last_login_at');
            $table->timestamp('ai_chat_blocked_until')->nullable()->after('ai_chat_blocked_at');
            $table->text('ai_chat_block_reason')->nullable()->after('ai_chat_blocked_until');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['ai_chat_blocked_at', 'ai_chat_blocked_until', 'ai_chat_block_reason']);
        });
    }
};
