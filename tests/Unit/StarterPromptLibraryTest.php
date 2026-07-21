<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domain\Chat\Support\StarterPromptLibrary;
use Tests\TestCase;

/**
 * StarterPromptLibrary parses the "Starter Prompts (chat UI)" block of the RAG
 * question bank into the agent / answer-only starter lists the chat UI shows.
 */
class StarterPromptLibraryTest extends TestCase
{
    private function fixture(string $markdown): string
    {
        $path = tempnam(sys_get_temp_dir(), 'starter').'.md';
        file_put_contents($path, $markdown);

        return $path;
    }

    public function test_it_parses_both_mode_lists(): void
    {
        $path = $this->fixture(<<<'MD'
        # Question Bank

        ## Starter Prompts (chat UI)

        ### Agent Mode
        - What deadlines do I have coming up?
        - Quiz me on {course}

        ### Answer-Only Mode
        - How do I plan an effective study schedule?
        - Explain a key concept from {course}

        ## Coverage & Scoring Notes
        - This bullet must NOT be collected.
        MD);

        $library = new StarterPromptLibrary($path);
        $prompts = $library->all();

        $this->assertSame(
            ['What deadlines do I have coming up?', 'Quiz me on {course}'],
            $prompts['agent'],
        );
        $this->assertSame(
            ['How do I plan an effective study schedule?', 'Explain a key concept from {course}'],
            $prompts['answers'],
        );
        // The list stops at the next heading — trailing bullets are not swept in.
        $this->assertNotContains('This bullet must NOT be collected.', $prompts['answers']);
    }

    public function test_missing_file_yields_empty_lists(): void
    {
        $library = new StarterPromptLibrary('/no/such/question_bank.md');

        $this->assertSame(['agent' => [], 'answers' => []], $library->all());
    }

    public function test_the_real_question_bank_populates_both_lists(): void
    {
        $prompts = (new StarterPromptLibrary)->all();

        $this->assertNotEmpty($prompts['agent'], 'Agent starter prompts should be present in question_bank.md');
        $this->assertNotEmpty($prompts['answers'], 'Answer-only starter prompts should be present in question_bank.md');
    }
}
