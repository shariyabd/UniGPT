<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * `users.semester` held a free-text string (e.g. "5th Semester") while
 * `courses.semester` is an integer curriculum level. The frontend already
 * assumes a bare number (it renders "Semester {{ semester }}"), so a string
 * value produced bugs ("5th Semester Semester", broken roadmap matching).
 * Normalize to an unsigned integer curriculum level.
 */
return new class extends Migration
{
    public function up(): void
    {
        // MySQL parses leading digits: CAST('5th Semester' AS UNSIGNED) = 5.
        DB::statement("UPDATE users SET semester = CAST(semester AS UNSIGNED) WHERE semester IS NOT NULL AND semester <> ''");
        DB::statement("UPDATE users SET semester = NULL WHERE semester = '' OR semester = '0'");

        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('semester')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('semester')->nullable()->change();
        });
    }
};
