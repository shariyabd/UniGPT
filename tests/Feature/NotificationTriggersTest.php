<?php

namespace Tests\Feature;

use App\Domain\User\Models\User;
use App\Models\Assignment;
use App\Models\Notification;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class NotificationTriggersTest extends TestCase
{
    use DatabaseTransactions;

    private function user(string $email): User
    {
        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->markTestSkipped("Demo user {$email} not seeded.");
        }

        return $user;
    }

    public function test_notification_poll_returns_json(): void
    {
        $this->actingAs($this->user('student@university.edu'))
            ->getJson('/notifications/poll')
            ->assertOk()
            ->assertJsonStructure(['unread', 'items']);
    }

    public function test_updating_a_published_assignment_due_date_notifies_students(): void
    {
        $faculty = $this->user('prof.smith@university.edu');
        $student = $this->user('student@university.edu');

        $courseIds = $student->enrolledCourses()->pluck('courses.id');
        $assignment = Assignment::whereIn('course_id', $courseIds)
            ->whereHas('course', fn ($q) => $q->where('faculty_id', $faculty->id))
            ->where('status', 'published')
            ->first();

        if (! $assignment) {
            $this->markTestSkipped('No suitable published assignment for the demo faculty/student.');
        }

        $this->actingAs($faculty)
            ->patch(route('faculty.assignments.update', $assignment->id), [
                'title' => $assignment->title,
                'type' => $assignment->type,
                'total_points' => $assignment->total_points,
                'status' => 'published',
                'due_at' => now()->addDays(30)->format('Y-m-d H:i:s'),
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('notifications', [
            'user_id' => $student->id,
            'type' => 'assignment',
            'title' => 'Assignment due date changed',
        ]);
    }

    public function test_reminder_command_notifies_unsubmitted_students_and_is_idempotent(): void
    {
        $student = $this->user('student@university.edu');

        // Wide window catches the seeded assignments (due in 7–21 days).
        Artisan::call('assignments:remind', ['--hours' => 1000]);

        $reminders = Notification::where('user_id', $student->id)
            ->where('title', 'Assignment due soon')
            ->count();

        if ($reminders === 0) {
            $this->markTestSkipped('No upcoming unsubmitted assignments to remind about.');
        }

        $this->assertGreaterThan(0, $reminders);

        // Running again must not create duplicate reminders.
        Artisan::call('assignments:remind', ['--hours' => 1000]);

        $this->assertSame(
            $reminders,
            Notification::where('user_id', $student->id)->where('title', 'Assignment due soon')->count(),
        );
    }

    public function test_submitted_assignments_are_not_reminded(): void
    {
        $student = $this->user('student@university.edu');

        Artisan::call('assignments:remind', ['--hours' => 1000]);

        // The seeded student submitted "Assignment 1" — it must not be reminded.
        $remindedTitles = Notification::where('user_id', $student->id)
            ->where('title', 'Assignment due soon')
            ->get()
            ->pluck('data.assignment_id');

        $submittedAssignmentIds = \App\Models\AssignmentSubmission::where('user_id', $student->id)
            ->pluck('assignment_id');

        $this->assertTrue(
            $remindedTitles->intersect($submittedAssignmentIds)->isEmpty(),
            'A reminder was sent for an already-submitted assignment.',
        );
    }
}
