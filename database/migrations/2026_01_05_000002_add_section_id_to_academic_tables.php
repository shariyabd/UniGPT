<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Reparent course-scoped academic records onto a section. `course_id` is kept
 * (denormalized) so existing course-level queries keep working during the
 * transition; `section_id` is the new owning key.
 */
return new class extends Migration
{
    /**
     * @var array<int, string>
     */
    private array $tables = ['course_materials', 'assignments', 'attendance_records', 'exams'];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->foreignId('section_id')->nullable()->after('course_id')
                    ->constrained('sections')
                    ->onUpdate('cascade')->onDelete('cascade');
                $blueprint->index('section_id');
            });

            // Backfill from the course's (single) section.
            DB::statement("
                UPDATE {$table} AS t
                JOIN sections AS s ON s.course_id = t.course_id
                SET t.section_id = s.id
                WHERE t.section_id IS NULL
            ");
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropForeign(['section_id']);
                $blueprint->dropIndex([$blueprint->getTable().'_section_id_index']);
                $blueprint->dropColumn('section_id');
            });
        }
    }
};
