<?php

namespace Tests\Feature;

use App\Domain\Academic\Services\CourseService;
use App\Domain\Academic\Services\SubmissionService;
use App\Domain\User\Models\User;
use App\Models\Assignment;
use App\Models\CourseMaterial;
use App\Models\Section;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * A student must only ever see the content of the SECTION they are enrolled in,
 * and a faculty member only their own section's roster — never another section
 * of the same course. These tests create content in a sibling section and prove
 * it does not leak across the section boundary.
 */
class SectionIsolationTest extends TestCase
{
    use DatabaseTransactions;

    private function student(): User
    {
        $student = User::where('email', 'student@university.edu')->first();
        if (! $student) {
            $this->markTestSkipped('Demo student not seeded.');
        }

        return $student;
    }

    private function sectionByCode(string $code, string $label): Section
    {
        $section = Section::whereHas('course', fn ($q) => $q->where('code', $code))
            ->where('label', $label)
            ->first();
        if (! $section) {
            $this->markTestSkipped("{$code} section {$label} not seeded.");
        }

        return $section;
    }

    public function test_student_sees_their_own_sections_instructor(): void
    {
        $student = $this->student();
        $sectionA = $this->sectionByCode('CS301', 'A');

        $courses = app(CourseService::class)->studentCourses($student);
        $cs301 = $courses->firstWhere('code', 'CS301');

        $this->assertNotNull($cs301, 'Student should be enrolled in CS301.');
        $this->assertSame('A', $cs301['section']);
        $this->assertSame($sectionA->faculty?->name, $cs301['instructor']);
    }

    public function test_material_in_another_section_is_not_visible_to_student(): void
    {
        $student = $this->student();
        $sectionB = $this->sectionByCode('CS301', 'B'); // student is in section A, not B

        $foreign = CourseMaterial::create([
            'course_id' => $sectionB->course_id,
            'section_id' => $sectionB->id,
            'title' => 'Section B Only Slides',
            'type' => 'slides',
            'week' => 1,
            'is_published' => true,
            'uploaded_by' => $sectionB->faculty_id,
        ]);

        $materials = app(CourseService::class)->studentMaterials($student);
        $cs301 = $materials->firstWhere('code', 'CS301');

        $titles = collect($cs301['materials'] ?? [])->pluck('title');
        $this->assertFalse($titles->contains('Section B Only Slides'),
            'Material from a section the student is not in must not appear.');

        // And a direct download attempt is rejected.
        $this->actingAs($student)
            ->get(route('materials.download', $foreign->id))
            ->assertNotFound();
    }

    public function test_assignment_in_another_section_is_hidden_and_inaccessible(): void
    {
        $student = $this->student();
        $sectionB = $this->sectionByCode('CS301', 'B');

        $foreign = Assignment::create([
            'course_id' => $sectionB->course_id,
            'section_id' => $sectionB->id,
            'title' => 'Section B Only Assignment',
            'type' => 'homework',
            'total_points' => 100,
            'status' => 'published',
            'created_by' => $sectionB->faculty_id,
        ]);

        $listed = collect(app(SubmissionService::class)->listFor($student))->pluck('title');
        $this->assertFalse($listed->contains('Section B Only Assignment'),
            'Assignment from another section must not be listed.');

        $this->actingAs($student)
            ->get(route('assignments.show', $foreign->id))
            ->assertNotFound();
    }

    public function test_faculty_only_sees_their_own_sections_roster(): void
    {
        $sectionA = $this->sectionByCode('CS301', 'A');
        $sectionB = $this->sectionByCode('CS301', 'B');

        $smith = $sectionA->faculty;
        $jones = $sectionB->faculty;
        if (! $smith || ! $jones || $smith->id === $jones->id) {
            $this->markTestSkipped('CS301 needs two sections with distinct faculty.');
        }

        $cs = app(CourseService::class);

        $smithCs301 = $cs->facultyCourses($smith)->firstWhere('code', 'CS301');
        $jonesCs301 = $cs->facultyCourses($jones)->firstWhere('code', 'CS301');

        $this->assertNotNull($smithCs301);
        $this->assertNotNull($jonesCs301);

        // The demo student is enrolled in section A (Smith), not section B (Jones).
        $this->assertGreaterThan(0, $smithCs301['students'], 'Smith should see his section A roster.');
        $this->assertSame(0, $jonesCs301['students'], 'Jones must not see section A students.');
    }
}
