<?php

namespace App\Domain\Chat\Services;

use App\Domain\Chat\Contracts\AIProviderInterface;

/**
 * Generates teaching artifacts (quizzes, assignments) for faculty. Uses the
 * active LLM when available and falls back to deterministic synthesis so the
 * feature works without any API key.
 */
class TeachingAssistantService
{
    public function __construct(private readonly AIProviderInterface $provider) {}

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    public function generateQuiz(array $params): array
    {
        $topic = trim((string) ($params['topic'] ?? 'General Knowledge'));
        $count = max(1, min(20, (int) ($params['questionCount'] ?? 5)));
        $difficulty = (string) ($params['difficulty'] ?? 'medium');

        $prompt = "Generate a {$difficulty} quiz of {$count} multiple-choice questions on \"{$topic}\". "
            .'Respond ONLY with JSON: {"questions":[{"question","options":["a","b","c","d"],"answer":"a","explanation"}]}';

        $parsed = $this->tryJson($this->provider->chat([
            ['role' => 'system', 'content' => 'You are an expert exam author.'],
            ['role' => 'user', 'content' => $prompt],
        ])->content);

        $questions = $parsed['questions'] ?? $this->synthesizeQuestions($topic, $count);

        return [
            'title' => ucfirst($difficulty)." Quiz: {$topic}",
            'topic' => $topic,
            'difficulty' => $difficulty,
            'questions' => collect($questions)->take($count)->values()->map(fn ($q, $i) => [
                'id' => $i + 1,
                'question' => $q['question'] ?? "Question about {$topic}",
                'options' => $q['options'] ?? ['Option A', 'Option B', 'Option C', 'Option D'],
                'answer' => $q['answer'] ?? ($q['options'][0] ?? 'Option A'),
                'explanation' => $q['explanation'] ?? 'Refer to the course materials for details.',
                'points' => 1,
            ])->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    public function generateAssignment(array $params): array
    {
        $title = trim((string) ($params['title'] ?? 'Course Assignment'));
        $topics = $params['topics'] ?? [$title];
        $points = (int) ($params['points'] ?? 100);

        $prompt = "Create an assignment titled \"{$title}\" covering: ".implode(', ', (array) $topics).'. '
            .'Respond ONLY with JSON: {"description","tasks":["..."],"rubric":[{"criterion","points"}]}';

        $parsed = $this->tryJson($this->provider->chat([
            ['role' => 'system', 'content' => 'You are an experienced university instructor.'],
            ['role' => 'user', 'content' => $prompt],
        ])->content);

        return [
            'title' => $title,
            'description' => $parsed['description'] ?? "In this assignment you will explore {$title} and apply core concepts from the course.",
            'tasks' => $parsed['tasks'] ?? [
                "Research the key concepts behind {$title}.",
                'Apply the concepts to a worked example.',
                'Write up your findings with proper citations.',
            ],
            'rubric' => $parsed['rubric'] ?? [
                ['criterion' => 'Correctness', 'points' => (int) ($points * 0.5)],
                ['criterion' => 'Depth of analysis', 'points' => (int) ($points * 0.3)],
                ['criterion' => 'Presentation', 'points' => (int) ($points * 0.2)],
            ],
            'points' => $points,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function tryJson(string $content): ?array
    {
        if (preg_match('/\{.*\}/s', $content, $m)) {
            $decoded = json_decode($m[0], true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function synthesizeQuestions(string $topic, int $count): array
    {
        $templates = [
            'Which of the following best describes %s?',
            'What is a key principle of %s?',
            'In the context of %s, which statement is correct?',
            'Which technique is most associated with %s?',
            'What is a common application of %s?',
        ];

        $questions = [];
        for ($i = 0; $i < $count; $i++) {
            $questions[] = [
                'question' => sprintf($templates[$i % count($templates)], $topic),
                'options' => [
                    "A correct concept of {$topic}",
                    'A plausible but incorrect statement',
                    'An unrelated concept',
                    'None of the above',
                ],
                'answer' => "A correct concept of {$topic}",
                'explanation' => "This relates to the fundamentals of {$topic} covered in lectures.",
            ];
        }

        return $questions;
    }
}
