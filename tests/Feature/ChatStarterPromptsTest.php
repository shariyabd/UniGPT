<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\User\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * The student chat page is seeded with welcome-card starter suggestions sourced
 * from the RAG question bank (single source of truth), passed as the
 * `starterPrompts` prop with separate agent / answer-only lists.
 */
class ChatStarterPromptsTest extends TestCase
{
    use DatabaseTransactions;

    private function student(): User
    {
        $student = User::where('email', 'student@university.edu')->first();
        if (!$student) {
            $this->markTestSkipped('Demo student not seeded.');
        }

        return $student;
    }

    public function test_chat_index_shares_starter_prompts_for_both_modes(): void
    {
        $this->actingAs($this->student())
            ->get(route('chat'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Student/Chat')
                ->where('starterPrompts.agent', fn ($agent) => collect($agent)->isNotEmpty())
                ->where('starterPrompts.answers', fn ($answers) => collect($answers)->isNotEmpty())
            );
    }
}
