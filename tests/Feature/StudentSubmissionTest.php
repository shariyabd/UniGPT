<?php

namespace Tests\Feature;

use App\Domain\User\Models\User;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StudentSubmissionTest extends TestCase
{
    use DatabaseTransactions;

    private function student(): User
    {
        $student = User::where('email', 'student@university.edu')->first();

        if (! $student) {
            $this->markTestSkipped('Demo student not seeded; run php artisan db:seed.');
        }

        return $student;
    }

    /**
     * A published assignment in a course the demo student is enrolled in.
     */
    private function publishedAssignment(User $student): Assignment
    {
        $courseIds = $student->enrolledCourses()->pluck('courses.id');

        $assignment = Assignment::whereIn('course_id', $courseIds)
            ->where('status', 'published')
            ->first();

        if (! $assignment) {
            $this->markTestSkipped('No published assignment in the student\'s courses.');
        }

        return $assignment;
    }

    public function test_student_can_view_the_assignments_list(): void
    {
        $this->actingAs($this->student())
            ->get('/assignments')
            ->assertOk();
    }

    public function test_student_can_open_a_published_assignment(): void
    {
        $student = $this->student();
        $assignment = $this->publishedAssignment($student);

        $this->actingAs($student)
            ->get("/assignments/{$assignment->id}")
            ->assertOk();
    }

    public function test_student_can_submit_written_work(): void
    {
        $student = $this->student();
        $assignment = $this->publishedAssignment($student);

        $this->actingAs($student)
            ->post("/assignments/{$assignment->id}/submit", [
                'content' => 'My submitted answer for this assignment.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('assignment_submissions', [
            'assignment_id' => $assignment->id,
            'user_id' => $student->id,
            'content' => 'My submitted answer for this assignment.',
        ]);
    }

    public function test_student_can_attach_a_file(): void
    {
        Storage::fake('local');

        $student = $this->student();
        $assignment = $this->publishedAssignment($student);

        $this->actingAs($student)
            ->post("/assignments/{$assignment->id}/submit", [
                'file' => UploadedFile::fake()->create('answer.pdf', 64, 'application/pdf'),
            ])
            ->assertRedirect();

        $submission = AssignmentSubmission::where('assignment_id', $assignment->id)
            ->where('user_id', $student->id)
            ->first();

        $this->assertNotNull($submission->file_path);
        $this->assertSame('answer.pdf', $submission->original_filename);
    }

    public function test_submission_requires_content_or_file(): void
    {
        $student = $this->student();
        $assignment = $this->publishedAssignment($student);

        $this->actingAs($student)
            ->post("/assignments/{$assignment->id}/submit", [])
            ->assertSessionHasErrors('content');
    }

    public function test_student_cannot_submit_to_a_draft_assignment(): void
    {
        $student = $this->student();
        $courseIds = $student->enrolledCourses()->pluck('courses.id');

        $draft = Assignment::whereIn('course_id', $courseIds)->first();
        if (! $draft) {
            $this->markTestSkipped('No assignment available to draft.');
        }

        $original = $draft->status;
        $draft->update(['status' => 'draft']);

        try {
            $this->actingAs($student)
                ->get("/assignments/{$draft->id}")
                ->assertNotFound();
        } finally {
            $draft->update(['status' => $original]);
        }
    }
}
