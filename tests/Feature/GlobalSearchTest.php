<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Chat\Contracts\AIProviderInterface;
use App\Domain\RAG\Ingestion\PersonalCorpusService;
use App\Domain\User\Models\User;
use App\Infrastructure\AI\MockProvider;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Global ⌘K search: semantic knowledge hits plus lexical entity groups,
 * scoped to the requesting user.
 */
class GlobalSearchTest extends TestCase
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
        if (! $student) {
            $this->markTestSkipped('Demo student not seeded; run php artisan db:seed.');
        }

        return $student;
    }

    public function test_search_requires_a_query(): void
    {
        $this->actingAs($this->student())
            ->getJson('/search')
            ->assertUnprocessable();
    }

    public function test_guests_cannot_search(): void
    {
        $this->getJson('/search?q=algebra')->assertUnauthorized();
    }

    public function test_search_returns_grouped_results(): void
    {
        $student = $this->student();

        $response = $this->actingAs($student)->getJson('/search?q=algorithm');

        $response->assertOk();
        $groups = $response->json('groups');

        $this->assertIsArray($groups);
        foreach ($groups as $group) {
            $this->assertArrayHasKey('label', $group);
            $this->assertNotEmpty($group['items']);
            foreach ($group['items'] as $item) {
                $this->assertArrayHasKey('title', $item);
                $this->assertArrayHasKey('url', $item);
                $this->assertArrayHasKey('badge', $item);
            }
        }
    }

    public function test_own_notes_surface_semantically_in_knowledge_group(): void
    {
        $student = $this->student();

        $note = $student->notes()->create([
            'title' => 'Okapi ranking recap',
            'content' => 'The okapi bm25 weighting balances term frequency against document length.',
        ]);
        app(PersonalCorpusService::class)->syncNote($note);

        $groups = $this->actingAs($student)
            ->getJson('/search?q='.urlencode('okapi bm25 weighting'))
            ->json('groups');

        $knowledge = collect($groups)->firstWhere('label', 'Knowledge');

        $this->assertNotNull($knowledge, 'Knowledge group should be present.');
        $this->assertContains('Okapi ranking recap', array_column($knowledge['items'], 'title'));
    }

    public function test_enrolled_course_matches_by_code(): void
    {
        $student = $this->student();
        $course = $student->enrolledCourses()->wherePivotNotIn('status', ['pending'])->first();

        if (! $course) {
            $this->markTestSkipped('Demo student has no enrolled courses.');
        }

        $groups = $this->actingAs($student)
            ->getJson('/search?q='.urlencode($course->code))
            ->json('groups');

        $courses = collect($groups)->firstWhere('label', 'Courses');

        $this->assertNotNull($courses, 'Courses group should be present.');
        $this->assertTrue(
            collect($courses['items'])->contains(fn (array $item) => str_contains($item['title'], $course->code)),
        );
    }

    public function test_own_chat_history_is_searchable_and_deep_linked(): void
    {
        $student = $this->student();

        $session = $student->chatSessions()->create(['mode' => 'academic', 'language' => 'en']);
        $message = $session->messages()->create([
            'role' => 'user',
            'content' => 'Explain the zeppelin caching strategy please',
        ]);

        $groups = $this->actingAs($student)
            ->getJson('/search?q=zeppelin')
            ->json('groups');

        $chats = collect($groups)->firstWhere('label', 'Chat History');

        $this->assertNotNull($chats, 'Chat History group should be present.');
        $this->assertStringContainsString("session={$session->id}", $chats['items'][0]['url']);
        $this->assertStringContainsString("message={$message->id}", $chats['items'][0]['url']);
    }

    public function test_other_users_chats_never_appear(): void
    {
        $student = $this->student();
        $other = User::where('id', '!=', $student->id)
            ->whereHas('roles', fn ($q) => $q->where('slug', 'student'))
            ->first();

        if (! $other) {
            $this->markTestSkipped('Second demo student not seeded.');
        }

        $session = $other->chatSessions()->create(['mode' => 'academic', 'language' => 'en']);
        $session->messages()->create([
            'role' => 'user',
            'content' => 'Private quixotic marmalade question',
        ]);

        $groups = $this->actingAs($student)
            ->getJson('/search?q=quixotic')
            ->json('groups');

        $chats = collect($groups)->firstWhere('label', 'Chat History');

        $this->assertNull($chats, 'Another user\'s chat content must never surface.');
    }
}
