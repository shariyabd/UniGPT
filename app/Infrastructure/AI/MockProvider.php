<?php

namespace App\Infrastructure\AI;

use App\Domain\Chat\Contracts\AIProviderInterface;
use App\Domain\Chat\DataObjects\ChatResult;

/**
 * Keyless, deterministic AI provider used as the default fallback.
 *
 * - chat() composes a grounded answer from the retrieved context that the
 *   RagChatService injects as a system message, so the demo experience reads
 *   like a real RAG assistant without any external API.
 * - embed() is a lexical hashing vectorizer: texts sharing words produce
 *   similar vectors, so cosine-similarity retrieval returns sensible results.
 */
class MockProvider implements AIProviderInterface
{
    private const DIMENSIONS = 256;

    private const MODEL = 'mock-embedding-v1';

    public function chat(array $messages, array $options = []): ChatResult
    {
        $question = $this->lastUserMessage($messages);

        // Function calling, keyless: if the caller offered tools and the last
        // turn is a tool result, summarize it; otherwise keyword-match the
        // question to at most one tool call so agentic flows demo end to end.
        if (! empty($options['tools'])) {
            if ($toolAnswer = $this->answerFromToolResults($messages)) {
                return $this->textResult($toolAnswer, $messages);
            }

            if ($toolCall = $this->matchTool($question, $options['tools'])) {
                return new ChatResult(
                    content: '',
                    model: 'mock-chat-v1',
                    promptTokens: (int) ceil(mb_strlen($question) / 4),
                    completionTokens: 0,
                    raw: ['provider' => 'mock'],
                    toolCalls: [$toolCall],
                );
            }
        }

        $context = $this->extractContext($messages);

        $answer = $context !== ''
            ? $this->groundedAnswer($question, $context)
            : $this->genericAnswer($question);

        return $this->textResult($answer, $messages);
    }

    public function chatStream(array $messages, callable $onDelta, array $options = []): ChatResult
    {
        $result = $this->chat($messages, $options);

        // A tool-call turn has no text to stream; the caller runs the tools
        // and calls back with their results.
        if ($result->toolCalls !== []) {
            return $result;
        }

        // Emit the deterministic answer word by word so the demo experience
        // shows a believable live-typing stream without an external API.
        foreach (preg_split('/(?<=\s)/u', $result->content) ?: [] as $piece) {
            if ($piece === '') {
                continue;
            }

            $onDelta($piece);

            if (! app()->runningUnitTests()) {
                usleep(15_000);
            }
        }

        return $result;
    }

    public function extractText(string $imagePath, string $mimeType): string
    {
        // Keyless fallback — no real OCR. Returns a deterministic placeholder so
        // the upload → transcribe → save-as-note flow works end to end in demos.
        return "[Demo OCR] Handwritten note transcription is unavailable without a vision-capable AI provider.\n\n"
            .'Set OPENAI_API_KEY and AI_DEFAULT_PROVIDER=openai to transcribe real images, then edit this text and save it as a note.';
    }

    public function embed(array $texts): array
    {
        return array_map(fn (string $text) => $this->hashVector($text), $texts);
    }

    public function embeddingDimensions(): int
    {
        return self::DIMENSIONS;
    }

    public function embeddingModel(): string
    {
        return self::MODEL;
    }

    public function name(): string
    {
        return 'mock';
    }

    public function isAvailable(): bool
    {
        return true;
    }

    private function textResult(string $answer, array $messages): ChatResult
    {
        return new ChatResult(
            content: $answer,
            model: 'mock-chat-v1',
            promptTokens: (int) ceil(mb_strlen(implode(' ', array_map(
                fn ($m) => is_string($m['content'] ?? null) ? $m['content'] : '',
                $messages,
            ))) / 4),
            completionTokens: (int) ceil(mb_strlen($answer) / 4),
            raw: ['provider' => 'mock'],
        );
    }

    /**
     * When the conversation ends in tool-role results, compose a deterministic
     * recap of them (the mock equivalent of the model's post-tool answer).
     */
    private function answerFromToolResults(array $messages): ?string
    {
        $last = end($messages);
        if (! is_array($last) || ($last['role'] ?? '') !== 'tool') {
            return null;
        }

        $lines = [];
        for ($i = count($messages) - 1; $i >= 0; $i--) {
            if (($messages[$i]['role'] ?? '') !== 'tool') {
                break;
            }
            $payload = json_decode((string) ($messages[$i]['content'] ?? ''), true);
            if (! is_array($payload)) {
                continue;
            }
            $lines[] = ($payload['status'] ?? '') === 'ok'
                ? '✓ '.($payload['summary'] ?? 'Done.')
                : '✗ '.($payload['error'] ?? 'The action failed.');
        }

        if ($lines === []) {
            return null;
        }

        return "Here's what I did with your academic tools:\n\n"
            .implode("\n", array_reverse($lines))
            ."\n\n*(Demo response from the built-in mock AI provider — set OPENAI_API_KEY and "
            .'AI_DEFAULT_PROVIDER=openai for full, live answers.)*';
    }

    /**
     * Keyword-route a question to at most one of the offered tools, mirroring
     * how a real model would pick a function. Returns null when no tool fits.
     *
     * @param  array<int, array<string, mixed>>  $tools
     * @return array{id: string, name: string, arguments: array<string, mixed>}|null
     */
    private function matchTool(string $question, array $tools): ?array
    {
        $available = array_map(fn ($tool) => (string) ($tool['function']['name'] ?? ''), $tools);
        $q = mb_strtolower($question);

        $call = function (string $name, array $arguments = []) use ($available): ?array {
            return in_array($name, $available, true)
                ? ['id' => 'mock-tool-1', 'name' => $name, 'arguments' => $arguments]
                : null;
        };

        if (preg_match('/deadline|due\s+(soon|date|this)|what.*due/', $q)) {
            return $call('get_upcoming_deadlines');
        }

        if (str_contains($q, 'office hour')) {
            if (preg_match('/\b(book|reserve)\b/', $q) && preg_match('/\b(?:slot\s*)?#?(\d+)\b/', $q, $m)) {
                return $call('book_office_hour_slot', ['slot_id' => (int) $m[1]]);
            }

            return $call('list_office_hour_slots');
        }

        if (preg_match('/quiz me|practice quiz|give me a quiz/', $q)) {
            return $call('generate_practice_quiz', ['topic' => $this->extractTopic($question)]);
        }

        if (str_contains($q, 'flashcard')) {
            return $call('generate_flashcard_deck', ['topic' => $this->extractTopic($question)]);
        }

        return null;
    }

    /**
     * The topic is whatever follows "on/about/for", else the whole question.
     */
    private function extractTopic(string $question): string
    {
        if (preg_match('/\b(?:on|about|for)\s+(.{3,})$/i', $question, $m)) {
            return trim($m[1], " .!?\t");
        }

        return trim($question, " .!?\t");
    }

    private function groundedAnswer(string $question, string $context): string
    {
        $snippet = $this->firstSentences($context, 3);

        return 'Based on the available course materials, here is what I found'
            .($question !== '' ? " regarding \"{$question}\"" : '').":\n\n"
            .$snippet."\n\n"
            .'This response is grounded in the cited sources below. For full detail, '
            .'open the referenced documents. *(Demo response generated by the built-in '
            .'mock AI provider — set OPENAI_API_KEY and AI_DEFAULT_PROVIDER=openai for live answers.)*';
    }

    private function genericAnswer(string $question): string
    {
        $topic = $question !== '' ? " about \"{$question}\"" : '';

        return "I don't have an approved course document that directly covers this, but I can still "
            ."help{$topic} from general academic knowledge.\n\n"
            .'Tell me a bit more about what you want to achieve — for example a concept to explain, a '
            .'topic to study, or a problem to work through — and I\'ll guide you step by step. If this '
            .'relates to one of your enrolled courses, mention it and I\'ll tailor the answer.'
            ."\n\n*(Demo response from the built-in mock AI provider — set OPENAI_API_KEY and "
            .'AI_DEFAULT_PROVIDER=openai for full, live answers.)*';
    }

    private function lastUserMessage(array $messages): string
    {
        for ($i = count($messages) - 1; $i >= 0; $i--) {
            if (($messages[$i]['role'] ?? '') === 'user') {
                return trim((string) ($messages[$i]['content'] ?? ''));
            }
        }

        return '';
    }

    private function extractContext(array $messages): string
    {
        foreach ($messages as $message) {
            $content = (string) ($message['content'] ?? '');
            if (($message['role'] ?? '') === 'system' && str_contains($content, 'Context:')) {
                return trim(substr($content, strpos($content, 'Context:') + 8));
            }
        }

        return '';
    }

    private function firstSentences(string $text, int $count): string
    {
        $text = preg_replace('/\s+/', ' ', trim($text)) ?? $text;
        $parts = preg_split('/(?<=[.!?])\s+/', $text) ?: [$text];

        return trim(implode(' ', array_slice($parts, 0, $count)));
    }

    /**
     * Deterministic lexical hashing embedding (bag-of-words → fixed dims, L2-normalized).
     *
     * @return array<int, float>
     */
    private function hashVector(string $text): array
    {
        $vector = array_fill(0, self::DIMENSIONS, 0.0);

        preg_match_all('/[a-z0-9]+/', mb_strtolower($text), $matches);
        foreach ($matches[0] as $token) {
            if (mb_strlen($token) < 2) {
                continue;
            }
            $bucket = crc32($token) % self::DIMENSIONS;
            $vector[$bucket] += 1.0;
        }

        $norm = sqrt(array_sum(array_map(fn ($v) => $v * $v, $vector)));
        if ($norm > 0) {
            $vector = array_map(fn ($v) => $v / $norm, $vector);
        }

        return $vector;
    }
}
