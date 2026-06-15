<?php

namespace Tests\Feature;

use App\Domain\User\Models\User;
use App\Models\ChatSession;
use App\Models\Department;
use App\Models\SavedAnswer;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StudentRoleTest extends TestCase
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

    public function test_student_can_view_all_dashboard_pages(): void
    {
        $student = $this->student();

        foreach (['/dashboard', '/chat', '/saved', '/roadmap', '/documents', '/materials', '/profile', '/settings'] as $uri) {
            $this->actingAs($student)
                ->get($uri)
                ->assertOk();
        }
    }

    public function test_student_can_send_a_chat_message_and_get_a_grounded_reply(): void
    {
        $student = $this->student();

        $response = $this->actingAs($student)->postJson('/chat', [
            'message' => 'What is the minimum attendance requirement?',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'session' => ['id', 'title'],
                'userMessage' => ['id', 'role', 'content'],
                'assistantMessage' => ['id', 'role', 'content', 'confidence', 'sources'],
            ]);

        $this->assertSame('assistant', $response->json('assistantMessage.role'));
    }

    public function test_student_cannot_access_admin_routes(): void
    {
        $this->actingAs($this->student())
            ->get('/admin/dashboard')
            ->assertRedirect();
    }

    public function test_student_cannot_view_another_users_chat_session(): void
    {
        $student = $this->student();
        $other = $this->makeOtherUser('other.chat@university.edu');
        $session = ChatSession::create(['user_id' => $other->id, 'title' => 'Private']);

        $this->actingAs($student)
            ->get("/chat/sessions/{$session->id}")
            ->assertForbidden();
    }

    public function test_student_cannot_delete_another_users_saved_answer(): void
    {
        $student = $this->student();
        $other = $this->makeOtherUser('other.saved@university.edu');
        $answer = SavedAnswer::create([
            'user_id' => $other->id,
            'title' => 'Theirs',
            'answer' => 'Private answer content.',
        ]);

        $this->actingAs($student)
            ->delete("/saved/{$answer->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('saved_answers', ['id' => $answer->id]);
    }

    private function makeOtherUser(string $email): User
    {
        return User::create([
            'name' => 'Other Student',
            'email' => $email,
            'password' => Hash::make('password123'),
            'is_active' => true,
            'email_verified_at' => now(),
            'department_id' => Department::query()->value('id'),
        ]);
    }
}
