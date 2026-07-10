<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Academic\Services\CourseFeedbackService;
use App\Domain\Chat\Contracts\AIProviderInterface;
use App\Domain\User\Models\User;
use App\Infrastructure\AI\MockProvider;
use App\Models\CourseFeedback;
use App\Models\Notification;
use App\Models\Section;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Anonymous mid-semester course feedback: faculty open a per-section window,
 * enrolled students submit one editable response each, and results reach the
 * faculty only in anonymized aggregate form once the response floor is met.
 */
class CourseFeedbackTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->bind(AIProviderInterface::class, fn () => new MockProvider);
    }

    private function student(): User
    {
        $student = User::where('email', 'student@university.edu')->first();
        if (! $student || $student->enrolledSectionIds()->isEmpty()) {
            $this->markTestSkipped('Demo student not seeded or has no sections.');
        }

        return $student;
    }

    private function sectionOf(User $student): Section
    {
        return Section::whereIn('id', $student->enrolledSectionIds())
            ->whereNotNull('faculty_id')
            ->firstOrFail();
    }

    public function test_student_can_submit_and_revise_one_response_while_open(): void
    {
        $student = $this->student();
        $section = $this->sectionOf($student);
        $section->update(['feedback_open' => true]);

        $this->actingAs($student)
            ->post("/course-feedback/{$section->id}", ['rating' => 4, 'comment' => 'Great pacing.'])
            ->assertRedirect();

        $this->actingAs($student)
            ->post("/course-feedback/{$section->id}", ['rating' => 2, 'comment' => 'Revised opinion.'])
            ->assertRedirect();

        $rows = CourseFeedback::where('section_id', $section->id)->where('user_id', $student->id)->get();
        $this->assertCount(1, $rows, 'One response per student per section.');
        $this->assertSame(2, $rows->first()->rating);
        $this->assertSame('Revised opinion.', $rows->first()->comment);
    }

    public function test_submissions_are_rejected_when_the_window_is_closed(): void
    {
        $student = $this->student();
        $section = $this->sectionOf($student);
        $section->update(['feedback_open' => false]);

        $this->actingAs($student)
            ->postJson("/course-feedback/{$section->id}", ['rating' => 5])
            ->assertStatus(422);
    }

    public function test_students_cannot_rate_sections_they_are_not_enrolled_in(): void
    {
        $student = $this->student();
        $other = Section::whereNotIn('id', $student->enrolledSectionIds())->first();
        if (! $other) {
            $this->markTestSkipped('No non-enrolled section available.');
        }
        $other->update(['feedback_open' => true]);

        $this->actingAs($student)
            ->postJson("/course-feedback/{$other->id}", ['rating' => 5])
            ->assertForbidden();
    }

    public function test_faculty_toggle_notifies_roster_and_results_hide_below_the_floor(): void
    {
        $student = $this->student();
        $section = $this->sectionOf($student);
        $faculty = User::findOrFail($section->faculty_id);

        $this->actingAs($faculty)
            ->patch("/faculty/course-feedback/{$section->id}/toggle")
            ->assertRedirect();

        $this->assertTrue($section->fresh()->feedback_open);
        $this->assertTrue(
            Notification::where('user_id', $student->id)->where('title', 'Course feedback requested')->exists(),
            'Opening the window notifies enrolled students.',
        );

        // One response is below the 3-response anonymity floor.
        CourseFeedback::create(['section_id' => $section->id, 'user_id' => $student->id, 'rating' => 1, 'comment' => 'Identifiable rant']);

        $data = app(CourseFeedbackService::class)->sectionsForFaculty($faculty)
            ->firstWhere('sectionId', $section->id);

        $this->assertFalse($data['revealed']);
        $this->assertSame(1, $data['responseCount']);
        $this->assertSame([], $data['comments']);
        $this->assertNull($data['averageRating']);
    }

    public function test_results_reveal_anonymized_once_the_floor_is_met(): void
    {
        $student = $this->student();
        $section = $this->sectionOf($student);
        $faculty = User::findOrFail($section->faculty_id);

        // Three classmates respond (direct inserts — enrolment is enforced on
        // the HTTP path, which the earlier tests cover).
        $classmates = $section->students()->limit(3)->get();
        if ($classmates->count() < 3) {
            $this->markTestSkipped('Section roster too small for the reveal test.');
        }
        foreach ($classmates as $index => $classmate) {
            CourseFeedback::create([
                'section_id' => $section->id,
                'user_id' => $classmate->id,
                'rating' => 4,
                'comment' => "Comment number {$index}",
            ]);
        }

        $data = app(CourseFeedbackService::class)->sectionsForFaculty($faculty)
            ->firstWhere('sectionId', $section->id);

        $this->assertTrue($data['revealed']);
        $this->assertSame(4.0, $data['averageRating']);
        $this->assertCount(3, $data['comments']);
        $this->assertSame(3, $data['ratingDistribution'][4]);

        // Anonymity: nothing in the payload identifies a respondent — no
        // user_id key, no names/emails, no timestamps.
        $json = (string) json_encode($data);
        foreach ($classmates as $classmate) {
            $this->assertStringNotContainsString($classmate->email, $json);
            $this->assertStringNotContainsString($classmate->name, $json);
        }
        $this->assertStringNotContainsString('user_id', $json);
        $this->assertStringNotContainsString('created_at', $json);

        // AI summary endpoint works once revealed (mock → heuristic fallback).
        $summary = $this->actingAs($faculty)
            ->postJson("/faculty/course-feedback/{$section->id}/summarize")
            ->assertOk()
            ->json();

        $this->assertArrayHasKey('summary', $summary);
        $this->assertSame('heuristic', $summary['source']);
    }

    public function test_only_the_owning_faculty_can_toggle_or_summarize(): void
    {
        $student = $this->student();
        $section = $this->sectionOf($student);

        $otherFaculty = User::where('id', '!=', $section->faculty_id)
            ->whereHas('roles', fn ($q) => $q->where('slug', 'faculty'))
            ->first();
        if (! $otherFaculty) {
            $this->markTestSkipped('No second faculty user seeded.');
        }

        $this->actingAs($otherFaculty)
            ->patchJson("/faculty/course-feedback/{$section->id}/toggle")
            ->assertForbidden();
        $this->actingAs($otherFaculty)
            ->postJson("/faculty/course-feedback/{$section->id}/summarize")
            ->assertForbidden();
    }
}
