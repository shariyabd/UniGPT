<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A section (offering) is one instance of a catalog Course taught in a given
 * term by a faculty member. Materials, assignments, attendance, exams and
 * enrolments hang off a section. The catalog Course stays as the shared
 * definition (code, title, credits).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()
                ->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('term_id')->nullable()->constrained('terms')
                ->onUpdate('cascade')->onDelete('set null');
            $table->foreignId('faculty_id')->nullable()->constrained('users')
                ->onUpdate('cascade')->onDelete('set null');
            $table->string('label')->default('A');   // Section A, B, C, ...
            $table->json('schedule')->nullable();
            $table->unsignedInteger('max_enrollment')->default(60);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['course_id', 'term_id', 'label']);
            $table->index('faculty_id');
            $table->index('term_id');
        });

        // Backfill: one section per existing course, carrying the course's
        // current offering attributes. Fresh installs have no courses yet — the
        // seeder creates sections when it seeds courses.
        $currentTermId = DB::table('terms')->where('is_current', true)->value('id');

        foreach (DB::table('courses')->get() as $course) {
            DB::table('sections')->insert([
                'course_id' => $course->id,
                'term_id' => $currentTermId,
                'faculty_id' => $course->faculty_id,
                'label' => 'A',
                'schedule' => $course->schedule,
                'max_enrollment' => $course->max_enrollment,
                'is_active' => $course->is_active,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sections');
    }
};
