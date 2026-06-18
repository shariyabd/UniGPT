<?php

namespace Tests\Feature;

use App\Domain\User\Models\User;
use App\Models\Course;
use App\Models\Section;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SectionStructureTest extends TestCase
{
    use DatabaseTransactions;

    public function test_every_course_has_at_least_one_section(): void
    {
        if (Course::count() === 0) {
            $this->markTestSkipped('No courses seeded.');
        }

        $coursesWithoutSection = Course::doesntHave('sections')->count();

        $this->assertSame(0, $coursesWithoutSection);
    }

    public function test_a_course_can_have_multiple_sections(): void
    {
        $cs301 = Course::where('code', 'CS301')->first();

        if (! $cs301) {
            $this->markTestSkipped('CS301 not seeded.');
        }

        $this->assertGreaterThanOrEqual(2, $cs301->sections()->count());
        $this->assertEqualsCanonicalizing(
            ['A', 'B'],
            $cs301->sections()->pluck('label')->all(),
        );
    }

    public function test_academic_records_are_attached_to_a_section(): void
    {
        if (Course::count() === 0) {
            $this->markTestSkipped('No courses seeded.');
        }

        foreach (['course_materials', 'assignments', 'attendance_records', 'exams'] as $table) {
            $this->assertSame(
                0,
                DB::table($table)->whereNotNull('course_id')->whereNull('section_id')->count(),
                "{$table} has rows without a section_id",
            );
        }

        $this->assertSame(
            0,
            DB::table('course_user')->whereNull('section_id')->count(),
            'course_user has enrollments without a section_id',
        );
    }

    public function test_new_material_auto_fills_section_id_from_course(): void
    {
        $course = Course::has('sections')->first();

        if (! $course) {
            $this->markTestSkipped('No course with a section.');
        }

        $material = $course->materials()->create([
            'title' => 'Hook test material',
            'type' => 'lecture',
            'is_published' => false,
        ]);

        $this->assertNotNull($material->section_id);
        $this->assertSame($course->primarySection()->id, $material->section_id);
    }

    public function test_faculty_teaching_sections_relation_works(): void
    {
        $faculty = User::where('email', 'prof.smith@university.edu')->first();

        if (! $faculty) {
            $this->markTestSkipped('Demo faculty not seeded.');
        }

        $this->assertGreaterThan(0, $faculty->teachingSections()->count());
        $this->assertContainsOnlyInstancesOf(Section::class, $faculty->teachingSections()->get());
    }
}
