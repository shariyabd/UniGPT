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
     * Draft constructive feedback for a graded submission. Faculty edit the
     * result before saving — this just gives them a starting point.
     *
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    public function generateFeedback(array $params): array
    {
        $assignment = trim((string) ($params['assignmentTitle'] ?? 'the assignment'));
        $grade = $params['grade'] ?? null;
        $total = $params['totalPoints'] ?? null;
        $excerpt = trim((string) ($params['submissionExcerpt'] ?? ''));
        $criteria = array_filter(array_map('strval', (array) ($params['rubricCriteria'] ?? [])));

        $scoreLine = ($grade !== null && $total)
            ? "The student scored {$grade} out of {$total}. "
            : '';
        $rubricLine = $criteria !== []
            ? 'Grading criteria: '.implode(', ', $criteria).'. '
            : '';
        $excerptLine = $excerpt !== ''
            ? 'Submission excerpt: "'.mb_substr($excerpt, 0, 600).'". '
            : '';

        $prompt = "Write constructive, encouraging feedback for a student submission for \"{$assignment}\". "
            .$scoreLine.$rubricLine.$excerptLine
            .'Respond ONLY with JSON: {"feedback":"2-4 sentences","strengths":["..."],"improvements":["..."]}';

        $parsed = $this->tryJson($this->provider->chat([
            ['role' => 'system', 'content' => 'You are an experienced, supportive university instructor giving submission feedback.'],
            ['role' => 'user', 'content' => $prompt],
        ])->content);

        $strengths = $parsed['strengths'] ?? $this->fallbackStrengths($grade, $total);
        $improvements = $parsed['improvements'] ?? $this->fallbackImprovements($grade, $total);
        $feedback = $parsed['feedback'] ?? $this->fallbackFeedback($assignment, $grade, $total);

        return [
            'feedback' => trim((string) $feedback),
            'strengths' => array_values((array) $strengths),
            'improvements' => array_values((array) $improvements),
        ];
    }

    private function ratio(mixed $grade, mixed $total): ?float
    {
        return ($grade !== null && $total) ? (float) $grade / (float) $total : null;
    }

    private function fallbackFeedback(string $assignment, mixed $grade, mixed $total): string
    {
        $ratio = $this->ratio($grade, $total);

        return match (true) {
            $ratio === null => "Thank you for your submission on {$assignment}. You demonstrate a working grasp of the material; review the feedback below to strengthen future work.",
            $ratio >= 0.85 => "Excellent work on {$assignment}. Your submission shows strong understanding and careful execution. Keep applying this rigour going forward.",
            $ratio >= 0.6 => "Good effort on {$assignment}. You cover the core ideas well, with room to deepen your analysis and presentation in places.",
            default => "Thanks for submitting {$assignment}. Several core concepts need more attention — please review the points below and reach out during office hours if helpful.",
        };
    }

    /**
     * @return array<int, string>
     */
    private function fallbackStrengths(mixed $grade, mixed $total): array
    {
        $ratio = $this->ratio($grade, $total);

        return $ratio !== null && $ratio >= 0.6
            ? ['Clear understanding of the main concepts', 'Well-structured submission']
            : ['Engaged with the assignment and attempted all parts'];
    }

    /**
     * @return array<int, string>
     */
    private function fallbackImprovements(mixed $grade, mixed $total): array
    {
        $ratio = $this->ratio($grade, $total);

        return $ratio !== null && $ratio >= 0.85
            ? ['Push further with additional edge cases or deeper analysis']
            : ['Strengthen the depth of analysis', 'Support claims with evidence from course materials'];
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
