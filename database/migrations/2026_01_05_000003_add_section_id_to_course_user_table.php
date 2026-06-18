<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A student enrols in a section (offering). `course_id` is retained so the
 * existing course-level enrollment queries keep working.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('course_user', function (Blueprint $table) {
            $table->foreignId('section_id')->nullable()->after('course_id')
                ->constrained('sections')
                ->onUpdate('cascade')->onDelete('cascade');
            $table->index('section_id');
        });

        // Backfill onto the course's (single) section, matching the term where
        // possible so existing per-term enrollments map to the right section.
        DB::statement('
            UPDATE course_user AS cu
            JOIN sections AS s ON s.course_id = cu.course_id
                AND (s.term_id = cu.term_id OR s.term_id IS NULL OR cu.term_id IS NULL)
            SET cu.section_id = s.id
            WHERE cu.section_id IS NULL
        ');
    }

    public function down(): void
    {
        Schema::table('course_user', function (Blueprint $table) {
            $table->dropForeign(['section_id']);
            $table->dropIndex(['course_user_section_id_index']);
            $table->dropColumn('section_id');
        });
    }
};
