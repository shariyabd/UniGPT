<?php

namespace Tests\Feature;

use App\Domain\Academic\Services\CourseService;
use App\Domain\User\Models\User;
use App\Models\Course;
use App\Models\CourseMaterial;
use App\Models\Exam;
use App\Models\Section;
use App\Models\Term;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Covers the two multi-section capabilities:
 *  1. A faculty member teaching several sections of one course works on one
 *     section at a time (roster/materials scoped to the chosen section).
 *  2. An admin schedules an exam for a specific section, or for every section
 *     of a course at once.
 */
class MultiSectionTest extends TestCase
{
    use DatabaseTransactions;

    private function smith(): User
    {
        $u = User::where('email', 'prof.smith@university.edu')->first();
        if (! $u) {
            $this->markTestSkipped('Demo faculty not seeded.');
        }

        return $u;
    }

    private function admin(): User
    {
        $u = User::where('email', 'admin@university.edu')->first();
        if (! $u) {
            $this->markTestSkipped('Demo admin not seeded.');
        }

        return $u;
    }

    private function course(string $code): Course
    {
        $c = Course::where('code', $code)->first();
        if (! $c) {
            $this->markTestSkipped("{$code} not seeded.");
        }

        return $c;
    }

    /**
     * Add a second section of a course taught by the given faculty so we can
     * exercise the multi-section flows.
     */
    private function addSection(Course $course, User $faculty, string $label): Section
    {
        return $course->sections()->create([
            'term_id' => Term::where('is_current', true)->value('id'),
            'faculty_id' => $faculty->id,
            'label' => $label,
            'max_enrollment' => 40,
            'is_active' => true,
        ]);
    }

    public function test_course_detail_lists_all_faculty_sections_and_scopes_to_the_active_one(): void
    {
        $smith = $this->smith();
        $cs305 = $this->course('CS305');           // student is enrolled in section A
        $sectionB = $this->addSection($cs305, $smith, 'B'); // empty new section

        $cs = app(CourseService::class);

        // Default (no section) → first section A, which has the enrolled student.
        $detailA = $cs->courseDetail($cs305, $smith);
        $this->assertCount(2, $detailA['sections'], 'Both sections should be selectable.');
        $this->assertGreaterThan(0, count($detailA['students']), 'Section A has the enrolled student.');

        // Explicitly viewing section B → empty roster (no one enrolled there).
        $detailB = $cs->courseDetail($cs305, $smith, $sectionB->id);
        $this->assertSame($sectionB->id, $detailB['activeSectionId']);
        $this->assertCount(0, $detailB['students'], 'Section B roster must not leak section A students.');
    }

    public function test_material_uploads_to_the_chosen_section(): void
    {
        $smith = $this->smith();
        $cs305 = $this->course('CS305');
        $sectionB = $this->addSection($cs305, $smith, 'B');

        $this->actingAs($smith)
            ->post(route('faculty.courses.materials.store', $cs305->id), [
                'section_id' => $sectionB->id,
                'title' => 'Section B Lecture',
                'type' => 'lecture',
                'is_published' => true,
            ])
            ->assertRedirect();

        $material = CourseMaterial::where('title', 'Section B Lecture')->first();
        $this->assertNotNull($material);
        $this->assertSame($sectionB->id, $material->section_id,
            'Material must attach to the section the faculty selected.');
    }

    public function test_admin_can_schedule_an_exam_for_a_specific_section(): void
    {
        $admin = $this->admin();
        $cs301 = $this->course('CS301');           // seeded with sections A and B
        $sectionA = $cs301->sections()->where('label', 'A')->first();
        if (! $sectionA || $cs301->sections()->count() < 2) {
            $this->markTestSkipped('CS301 needs at least two sections.');
        }

        $this->actingAs($admin)
            ->post(route('admin.exams.store'), [
                'course_id' => $cs301->id,
                'section_id' => $sectionA->id,
                'title' => 'Section A Only Exam',
                'type' => 'quiz',
                'exam_date' => now()->addDays(5)->toDateString(),
            ])
            ->assertRedirect();

        $exams = Exam::where('title', 'Section A Only Exam')->get();
        $this->assertCount(1, $exams, 'A specific section yields exactly one exam.');
        $this->assertSame($sectionA->id, $exams->first()->section_id);
    }

    public function test_admin_scheduling_without_a_section_covers_every_section(): void
    {
        $admin = $this->admin();
        $cs301 = $this->course('CS301');
        $sectionCount = $cs301->sections()->count();
        if ($sectionCount < 2) {
            $this->markTestSkipped('CS301 needs at least two sections.');
        }

        $this->actingAs($admin)
            ->post(route('admin.exams.store'), [
                'course_id' => $cs301->id,
                // no section_id → all sections
                'title' => 'All Sections Final',
                'type' => 'final',
                'exam_date' => now()->addDays(10)->toDateString(),
            ])
            ->assertRedirect();

        $exams = Exam::where('title', 'All Sections Final')->get();
        $this->assertCount($sectionCount, $exams,
            'One exam should be created per section of the course.');
        $this->assertEqualsCanonicalizing(
            $cs301->sections()->pluck('id')->all(),
            $exams->pluck('section_id')->all(),
            'Every section should get its own exam row.',
        );
    }
}
