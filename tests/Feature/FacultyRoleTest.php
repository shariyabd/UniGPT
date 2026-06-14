<?php

namespace Tests\Feature;

use App\Domain\User\Models\User;
use App\Models\AssignmentSubmission;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class FacultyRoleTest extends TestCase
{
    use DatabaseTransactions;

    private function faculty(): User
    {
        $faculty = User::where('email', 'prof.smith@university.edu')->first();

        if (! $faculty) {
            $this->markTestSkipped('Demo faculty not seeded; run php artisan db:seed.');
        }

        return $faculty;
    }

    public function test_faculty_can_view_core_pages(): void
    {
        $faculty = $this->faculty();
        $course = $faculty->teachingCourses()->first();

        $uris = ['/faculty/dashboard', '/faculty/ai-assistant', '/faculty/grading', '/faculty/courses'];
        if ($course) {
            $uris[] = "/faculty/courses/{$course->id}";
            $uris[] = "/faculty/courses/{$course->id}/grading";
        }

        foreach ($uris as $uri) {
            $this->actingAs($faculty)->get($uri)->assertOk();
        }
    }

    public function test_faculty_can_generate_a_quiz(): void
    {
        $this->actingAs($this->faculty())
            ->postJson('/faculty/ai-assistant/quiz', [
                'topic' => 'Binary Search Trees',
                'difficulty' => 'medium',
                'questionCount' => 3,
            ])
            ->assertOk()
            ->assertJsonStructure(['quiz' => ['title', 'questions' => [['id', 'question', 'options', 'answer']]]]);
    }

    public function test_faculty_can_generate_an_assignment(): void
    {
        $this->actingAs($this->faculty())
            ->postJson('/faculty/ai-assistant/assignment', [
                'title' => 'Sorting Algorithms',
                'points' => 100,
            ])
            ->assertOk()
            ->assertJsonStructure(['assignment' => ['title', 'description', 'tasks', 'rubric']]);
    }

    public function test_faculty_can_grade_a_submission(): void
    {
        $faculty = $this->faculty();

        $submission = AssignmentSubmission::whereHas(
            'assignment.course',
            fn ($q) => $q->where('faculty_id', $faculty->id)
        )->first();

        if (! $submission) {
            $this->markTestSkipped('No seeded submission to grade.');
        }

        $this->actingAs($faculty)
            ->post("/faculty/submissions/{$submission->id}/grade", [
                'grade' => 88,
                'feedback' => 'Solid work.',
            ])
            ->assertRedirect();

        $this->assertEquals(88.0, (float) $submission->fresh()->grade);
        $this->assertSame('graded', $submission->fresh()->status);
    }

    public function test_faculty_cannot_access_admin_routes(): void
    {
        $this->actingAs($this->faculty())->get('/admin/dashboard')->assertRedirect();
    }
}
