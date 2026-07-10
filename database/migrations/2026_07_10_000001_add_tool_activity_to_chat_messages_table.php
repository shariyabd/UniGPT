<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chat_messages', function (Blueprint $table) {
            // Agentic actions taken while answering (name/label/status/summary/link
            // per tool call), rendered as an activity trail on the message.
            $table->json('tool_activity')->nullable()->after('follow_ups');
        });
    }

    public function down(): void
    {
        Schema::table('chat_messages', function (Blueprint $table) {
            $table->dropColumn('tool_activity');
        });
    }
};
