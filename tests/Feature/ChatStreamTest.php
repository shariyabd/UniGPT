<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Chat\Contracts\AIProviderInterface;
use App\Domain\User\Models\User;
use App\Infrastructure\AI\MockProvider;
use App\Models\ChatMessage;
use App\Models\ChatSession;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * SSE streaming chat: the stream endpoints emit `delta` events while the
 * answer generates and a final `done` event whose payload matches the
 * non-streaming endpoint, with the conversation persisted identically.
 */
class ChatStreamTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        // Force the keyless deterministic provider so tests never call OpenAI.
        $this->app->bind(AIProviderInterface::class, fn () => new MockProvider);
    }

    private function student(): User
    {
        $student = User::where('email', 'student@university.edu')->first();
        if (! $student) {
            $this->markTestSkipped('Demo student not seeded; run php artisan db:seed.');
        }

        return $student;
    }

    private function faculty(): User
    {
        $faculty = User::where('email', 'prof.smith@university.edu')->first();
        if (! $faculty) {
            $this->markTestSkipped('Demo faculty not seeded; run php artisan db:seed.');
        }

        return $faculty;
    }

    public function test_student_chat_stream_emits_deltas_then_done_and_persists(): void
    {
        $student = $this->student();

        $response = $this->actingAs($student)->post('/chat/stream', [
            'message' => 'Explain the difference between a stack and a queue.',
        ]);

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/event-stream; charset=utf-8');

        $body = $response->streamedContent();

        $this->assertStringContainsString('event: delta', $body);
        $this->assertStringContainsString('event: done', $body);
        $this->assertStringNotContainsString('event: error', $body);

        // The done payload must match the non-streaming contract.
        preg_match('/event: done\ndata: (.+)\n\n/', $body, $matches);
        $done = json_decode($matches[1] ?? '', true);

        $this->assertIsArray($done);
        $this->assertArrayHasKey('session', $done);
        $this->assertSame('assistant', $done['assistantMessage']['role']);
        $this->assertNotSame('', $done['assistantMessage']['content']);

        // Streamed deltas concatenate to exactly the persisted answer.
        $deltas = '';
        foreach (explode("\n\n", $body) as $frame) {
            if (preg_match('/^event: delta\ndata: (.+)$/s', trim($frame), $m)) {
                $deltas .= json_decode($m[1], true)['text'] ?? '';
            }
        }
        $this->assertSame($done['assistantMessage']['content'], $deltas);

        $session = ChatSession::find($done['session']['id']);
        $this->assertNotNull($session);
        $this->assertSame($student->id, $session->user_id);
        $this->assertSame(2, $session->messages()->count());
        $this->assertSame(
            $done['assistantMessage']['content'],
            $session->messages()->where('role', ChatMessage::ROLE_ASSISTANT)->value('content'),
        );
    }

    public function test_small_talk_streams_the_canned_reply(): void
    {
        $student = $this->student();

        $body = $this->actingAs($student)
            ->post('/chat/stream', ['message' => 'hello'])
            ->streamedContent();

        $this->assertStringContainsString('event: delta', $body);
        $this->assertStringContainsString('event: done', $body);
    }

    public function test_faculty_assistant_stream_works(): void
    {
        $faculty = $this->faculty();

        $body = $this->actingAs($faculty)
            ->post('/faculty/ai-assistant/chat/stream', ['message' => 'Suggest a grading rubric for a lab report.'])
            ->streamedContent();

        $this->assertStringContainsString('event: delta', $body);
        $this->assertStringContainsString('event: done', $body);
        $this->assertStringNotContainsString('event: error', $body);
    }

    public function test_student_cannot_use_faculty_stream_endpoint(): void
    {
        // The role middleware bounces non-faculty back to their own dashboard.
        $response = $this->actingAs($this->student())
            ->post('/faculty/ai-assistant/chat/stream', ['message' => 'hi']);

        $this->assertTrue(
            $response->isRedirection() || $response->isForbidden(),
            'Students must not reach the faculty stream endpoint.',
        );
    }
}
