<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Running tally of AI-feature requests (chat, agent, faculty assistant,
 * generators) a user has made. Only enforced for the demo accounts, which are
 * capped at a small quota so the public demo cannot burn the shared API budget.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('ai_request_count')->default(0)->after('ai_chat_block_reason');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('ai_request_count');
        });
    }
};
