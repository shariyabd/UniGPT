<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Leaderboards are opt-in: students choose to appear, and may show an
            // alias instead of their real name.
            $table->boolean('leaderboard_opt_in')->default(false)->after('preferences');
            $table->string('leaderboard_alias')->nullable()->after('leaderboard_opt_in');

            $table->index('leaderboard_opt_in');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['leaderboard_opt_in']);
            $table->dropColumn(['leaderboard_opt_in', 'leaderboard_alias']);
        });
    }
};
