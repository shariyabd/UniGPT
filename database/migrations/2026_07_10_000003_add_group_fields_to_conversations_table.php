<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            // 'direct' (1:1 student↔faculty, the original messenger) or
            // 'group' (section-scoped study room with N student members).
            $table->string('type', 20)->default('direct')->after('id');
            // Group-only fields; always null on direct conversations.
            $table->string('title')->nullable()->after('type');
            $table->foreignId('section_id')->nullable()->after('title')
                ->constrained()
                ->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->after('section_id')
                ->constrained('users')
                ->cascadeOnUpdate()->nullOnDelete();

            $table->index(['type', 'section_id']);
        });
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by');
            $table->dropConstrainedForeignId('section_id');
            $table->dropIndex(['type', 'section_id']);
            $table->dropColumn(['type', 'title']);
        });
    }
};
