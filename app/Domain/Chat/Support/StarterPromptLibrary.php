<?php

declare(strict_types=1);

namespace App\Domain\Chat\Support;

use Illuminate\Support\Facades\Cache;

/**
 * Single source of truth for the AI-chat welcome-card starter prompts.
 *
 * The prompts live in the RAG question bank
 * (public/document-library/question_bank.md) under the "Starter Prompts (chat
 * UI)" section, split into "### Agent Mode" and "### Answer-Only Mode" lists.
 * This parser lifts those two lists so the frontend never hardcodes starters —
 * editing the markdown file is enough to change what students see.
 *
 * {course}/{course2} placeholders are left intact here; the frontend fills them
 * from the student's enrollments (and skips a prompt whose placeholder it can't
 * fill).
 */
class StarterPromptLibrary
{
    private const AGENT_HEADING = 'Agent Mode';

    private const ANSWERS_HEADING = 'Answer-Only Mode';

    public function __construct(
        private readonly string $path = '',
    ) {}

    /**
     * Both starter lists, keyed 'agent' and 'answers'. Cached against the
     * source file's modification time so edits are picked up without a manual
     * cache clear, yet repeated page loads don't re-read/parse the file.
     *
     * @return array{agent: list<string>, answers: list<string>}
     */
    public function all(): array
    {
        $file = $this->file();

        if (! is_file($file)) {
            return ['agent' => [], 'answers' => []];
        }

        $key = 'chat.starter_prompts:'.md5($file).':'.filemtime($file);

        return Cache::remember($key, now()->addDay(), fn () => $this->parse((string) file_get_contents($file)));
    }

    private function file(): string
    {
        return $this->path !== '' ? $this->path : public_path('document-library/question_bank.md');
    }

    /**
     * Pull the "- " list items under the Agent Mode and Answer-Only Mode
     * subheadings. Parsing stops at the next heading of any level, so the two
     * lists never bleed into surrounding sections.
     *
     * @return array{agent: list<string>, answers: list<string>}
     */
    private function parse(string $markdown): array
    {
        return [
            'agent' => $this->itemsUnder($markdown, self::AGENT_HEADING),
            'answers' => $this->itemsUnder($markdown, self::ANSWERS_HEADING),
        ];
    }

    /**
     * @return list<string>
     */
    private function itemsUnder(string $markdown, string $heading): array
    {
        $lines = preg_split('/\r\n|\r|\n/', $markdown) ?: [];
        $items = [];
        $collecting = false;

        foreach ($lines as $line) {
            $trimmed = trim($line);

            if (preg_match('/^#{1,6}\s+(.*)$/', $trimmed, $m)) {
                // A heading toggles collection on when it is our target and off
                // for any other heading (so the list ends at the next section).
                $collecting = trim($m[1]) === $heading;

                continue;
            }

            if (! $collecting) {
                continue;
            }

            if (preg_match('/^-\s+(.+)$/', $trimmed, $m)) {
                $prompt = trim($m[1]);
                if ($prompt !== '') {
                    $items[] = $prompt;
                }
            }
        }

        return $items;
    }
}
