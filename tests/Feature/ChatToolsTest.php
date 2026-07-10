<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Chat\Contracts\AIProviderInterface;
use App\Domain\User\Models\User;
use App\Infrastructure\AI\MockProvider;
use App\Models\ChatMessage;
use App\Models\OfficeHourSlot;
use App\Models\PracticeQuiz;
use App\Models\Section;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Agentic chat tools: the student AI chat can call functions (deadlines,
 * office-hour booking, quiz generation, ...) whose executions run through the
 * real domain services, stream tool_start/tool_result SSE events, and persist
 * a tool-activity trail on the assistant message.
 *
 * Uses the MockProvider, whose keyword routing deterministically requests
 * tools when the caller offers them.
 */
class ChatToolsTest extends TestCase
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

    private function facultyOf(User $student): User
    {
        $facultyId = Section::whereIn('id', $student->enrolledSectionIds())
            ->whereNotNull('faculty_id')
            ->value('faculty_id');

        if (! $facultyId) {
            $this->markTestSkipped('No faculty teaches the demo student.');
        }

        return User::findOrFail($facultyId);
    }

    private function openSlot(User $faculty): OfficeHourSlot
    {
        return OfficeHourSlot::create([
            'faculty_id' => $faculty->id,
            'starts_at' => now()->addDays(2)->setTime(15, 0),
            'ends_at' => now()->addDays(2)->setTime(15, 30),
            'location' => 'Room 101',
        ]);
    }

    /**
     * @return array{body: string, done: array<string, mixed>}
     */
    private function streamChat(User $student, string $message): array
    {
        $body = $this->actingAs($student)
            ->post('/chat/stream', ['message' => $message])
            ->assertOk()
            ->streamedContent();

        preg_match('/event: done\ndata: (.+)\n\n/', $body, $matches);
        $done = json_decode($matches[1] ?? '', true);
        $this->assertIsArray($done, "Stream must end with a done event. Body:\n{$body}");

        return ['body' => $body, 'done' => $done];
    }

    public function test_deadline_question_runs_the_deadlines_tool_and_persists_activity(): void
    {
        $student = $this->student();

        ['body' => $body, 'done' => $done] = $this->streamChat($student, 'What deadlines do I have coming up?');

        $this->assertStringContainsString('event: tool_start', $body);
        $this->assertStringContainsString('event: tool_result', $body);

        $activity = $done['assistantMessage']['toolActivity'];
        $this->assertCount(1, $activity);
        $this->assertSame('get_upcoming_deadlines', $activity[0]['name']);
        $this->assertSame('ok', $activity[0]['status']);

        // The trail is persisted on the message, not just streamed.
        $message = ChatMessage::find($done['assistantMessage']['id']);
        $this->assertSame('get_upcoming_deadlines', $message->tool_activity[0]['name']);
        $this->assertNotSame('', $message->content);
    }

    public function test_booking_office_hours_via_chat_actually_books_the_slot(): void
    {
        $student = $this->student();
        $faculty = $this->facultyOf($student);
        $slot = $this->openSlot($faculty);

        ['done' => $done] = $this->streamChat($student, "Please book office hour slot {$slot->id} for me");

        $slot->refresh();
        $this->assertSame($student->id, $slot->booked_by, 'The AI tool must book through the real service.');

        $activity = $done['assistantMessage']['toolActivity'];
        $this->assertSame('book_office_hour_slot', $activity[0]['name']);
        $this->assertSame('ok', $activity[0]['status']);
    }

    public function test_tool_failure_becomes_an_error_result_not_an_exception(): void
    {
        $student = $this->student();

        ['body' => $body, 'done' => $done] = $this->streamChat($student, 'Please book office hour slot 999999');

        $this->assertStringNotContainsString('event: error', $body);

        $activity = $done['assistantMessage']['toolActivity'];
        $this->assertSame('book_office_hour_slot', $activity[0]['name']);
        $this->assertSame('error', $activity[0]['status']);
        $this->assertNotSame('', $done['assistantMessage']['content']);
    }

    public function test_quiz_request_generates_a_real_practice_quiz(): void
    {
        $student = $this->student();
        $before = PracticeQuiz::where('user_id', $student->id)->count();

        ['done' => $done] = $this->streamChat($student, 'Quiz me on binary search trees');

        $this->assertSame($before + 1, PracticeQuiz::where('user_id', $student->id)->count());

        $activity = $done['assistantMessage']['toolActivity'];
        $this->assertSame('generate_practice_quiz', $activity[0]['name']);
        $this->assertSame('ok', $activity[0]['status']);
        $this->assertNotNull($activity[0]['link'], 'The activity entry should deep-link to the quiz.');
    }

    public function test_non_streaming_endpoint_returns_tool_activity_too(): void
    {
        $student = $this->student();

        $response = $this->actingAs($student)
            ->postJson('/chat', ['message' => 'What deadlines do I have this week?'])
            ->assertOk()
            ->json();

        $this->assertSame('get_upcoming_deadlines', $response['assistantMessage']['toolActivity'][0]['name']);
    }

    public function test_agent_mode_off_disables_tools_entirely(): void
    {
        $student = $this->student();

        // Same tool-triggering message as the deadline test, but with the
        // composer's Agent-mode toggle off → answers only.
        $body = $this->actingAs($student)
            ->post('/chat/stream', [
                'message' => 'What deadlines do I have coming up?',
                'agent' => false,
            ])
            ->assertOk()
            ->streamedContent();

        $this->assertStringNotContainsString('event: tool_start', $body);
        $this->assertStringContainsString('event: done', $body);

        preg_match('/event: done\ndata: (.+)\n\n/', $body, $matches);
        $done = json_decode($matches[1] ?? '', true);
        $this->assertSame([], $done['assistantMessage']['toolActivity']);
        $this->assertNotSame('', $done['assistantMessage']['content']);
    }

    public function test_faculty_assistant_gets_no_tools(): void
    {
        $faculty = User::where('email', 'prof.smith@university.edu')->first();
        if (! $faculty) {
            $this->markTestSkipped('Demo faculty not seeded.');
        }

        // The same keyword that triggers a tool for students must answer as
        // plain text for faculty (the registry only offers tools to students).
        $body = $this->actingAs($faculty)
            ->post('/faculty/ai-assistant/chat/stream', ['message' => 'What deadlines do I have coming up?'])
            ->assertOk()
            ->streamedContent();

        $this->assertStringNotContainsString('event: tool_start', $body);
        $this->assertStringContainsString('event: done', $body);
    }
}
