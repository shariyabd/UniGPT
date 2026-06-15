<?php

namespace Tests\Feature;

use App\Domain\User\Models\User;
use App\Models\Note;
use App\Models\Task;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ProductivityTest extends TestCase
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

    public function test_student_can_create_and_delete_a_note(): void
    {
        $student = $this->student();

        $this->actingAs($student)
            ->post('/notes', ['title' => 'Lecture takeaways', 'content' => 'Big O matters', 'is_pinned' => true])
            ->assertRedirect();

        $note = Note::where('user_id', $student->id)->where('title', 'Lecture takeaways')->first();
        $this->assertNotNull($note);
        $this->assertTrue($note->is_pinned);

        $this->actingAs($student)
            ->delete("/notes/{$note->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('notes', ['id' => $note->id]);
    }

    public function test_student_cannot_modify_another_users_note(): void
    {
        $student = $this->student();
        $admin = User::where('email', 'admin@university.edu')->first();
        $foreign = Note::create(['user_id' => $admin->id, 'title' => 'Private']);

        $this->actingAs($student)
            ->patch("/notes/{$foreign->id}", ['title' => 'Hijacked'])
            ->assertForbidden();
    }

    public function test_student_can_create_and_toggle_a_task(): void
    {
        $student = $this->student();

        $this->actingAs($student)
            ->post('/tasks', ['title' => 'Submit lab report', 'priority' => 'high', 'due_date' => now()->addDays(2)->toDateString()])
            ->assertRedirect();

        $task = Task::where('user_id', $student->id)->where('title', 'Submit lab report')->first();
        $this->assertNotNull($task);
        $this->assertFalse($task->is_completed);

        $this->actingAs($student)
            ->patch("/tasks/{$task->id}/toggle")
            ->assertRedirect();

        $task->refresh();
        $this->assertTrue($task->is_completed);
        $this->assertNotNull($task->completed_at);
    }

    public function test_task_validation_rejects_invalid_priority(): void
    {
        $this->actingAs($this->student())
            ->post('/tasks', ['title' => 'X', 'priority' => 'urgent'])
            ->assertSessionHasErrors('priority');
    }

    public function test_calendar_aggregates_events_and_loads(): void
    {
        $student = $this->student();

        Task::create([
            'user_id' => $student->id,
            'title' => 'Calendar task',
            'priority' => 'medium',
            'due_date' => now()->addDay()->toDateString(),
        ]);

        $this->actingAs($student)
            ->get('/calendar')
            ->assertOk();
    }
}
