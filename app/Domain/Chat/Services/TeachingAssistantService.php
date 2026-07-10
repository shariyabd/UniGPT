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
     * @var array<int, string>
     */
    private const QUESTION_TYPES = [
        'multiple-choice', 'true-false', 'short-answer', 'essay', 'fill-in-blank', 'matching',
    ];

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    public function generateQuiz(array $params): array
    {
        $topic = trim((string) ($params['topic'] ?? 'General Knowledge'));
        $count = max(1, min(20, (int) ($params['questionCount'] ?? 5)));
        $difficulty = (string) ($params['difficulty'] ?? 'medium');
        $bloom = (string) ($params['bloomLevel'] ?? 'apply');
        $explain = (bool) ($params['includeExplanations'] ?? true);

        $types = array_values(array_intersect(
            array_map('strval', (array) ($params['questionTypes'] ?? [])),
            self::QUESTION_TYPES,
        ));
        if ($types === []) {
            $types = ['multiple-choice'];
        }

        $typeList = implode(', ', $types);
        $prompt = "Generate a {$difficulty} quiz of {$count} questions on \"{$topic}\", targeting Bloom's level \"{$bloom}\". "
            ."Use ONLY these question types, mixed across the questions: {$typeList}. "
            .'Every question MUST include a "type" field equal to one of those values. '
            .'For "multiple-choice" include "options" (4 strings) and "answer" equal to the correct option text. '
            .'For "true-false" set "answer" to true or false. '
            .'For "short-answer", "essay", "fill-in-blank" and "matching" set "answer" to the expected/sample answer text. '
            .($explain ? 'Include a short "explanation" for every question. ' : '')
            .'Respond ONLY with JSON: {"questions":[{"type","question","options","answer","explanation"}]}';

        $parsed = $this->tryJson($this->provider->chat([
            ['role' => 'system', 'content' => 'You are an expert exam author.'],
            ['role' => 'user', 'content' => $prompt],
        ])->content);

        $questions = $parsed['questions'] ?? $this->synthesizeQuestions($topic, $count, $types);

        return [
            'title' => ucfirst($difficulty)." Quiz: {$topic}",
            'topic' => $topic,
            'difficulty' => $difficulty,
            'questionTypes' => $types,
            'questions' => collect($questions)->take($count)->values()->map(function ($q, $i) use ($topic, $types, $explain) {
                $type = $this->normalizeQuestionType($q['type'] ?? null, $q, $types, $i);
                $hasOptions = ! empty($q['options']) && is_array($q['options']);

                return [
                    'id' => $i + 1,
                    'type' => $type,
                    'question' => $q['question'] ?? "Question about {$topic}",
                    'options' => $type === 'multiple-choice'
                        ? ($hasOptions ? $q['options'] : ['Option A', 'Option B', 'Option C', 'Option D'])
                        : ($q['options'] ?? []),
                    'answer' => $q['answer'] ?? ($type === 'true-false' ? true : ($q['options'][0] ?? 'Sample answer')),
                    'explanation' => $explain ? ($q['explanation'] ?? "Refer to the course materials on {$topic}.") : null,
                    'points' => $q['points'] ?? 1,
                ];
            })->all(),
        ];
    }

    /**
     * Resolve a question's type from the model output, falling back to the
     * requested set so the UI always renders a sensible widget.
     *
     * @param  array<string, mixed>  $question
     * @param  array<int, string>  $requested
     */
    private function normalizeQuestionType(?string $type, array $question, array $requested, int $index): string
    {
        $type = $type ? strtolower(trim($type)) : null;
        if ($type !== null && in_array($type, self::QUESTION_TYPES, true)) {
            return $type;
        }
        if (! empty($question['options'])) {
            return 'multiple-choice';
        }
        if (isset($question['answer']) && is_bool($question['answer'])) {
            return 'true-false';
        }

        return $requested[$index % count($requested)] ?? 'multiple-choice';
    }

    /**
     * Generate front/back flashcards for a topic. Falls back to deterministic
     * synthesis so the feature works without any API key.
     *
     * @param  array<string, mixed>  $params
     * @return array<int, array{front: string, back: string}>
     */
    public function generateFlashcards(array $params): array
    {
        $topic = trim((string) ($params['topic'] ?? 'General Knowledge'));
        $count = max(1, min(30, (int) ($params['count'] ?? 10)));
        $difficulty = (string) ($params['difficulty'] ?? 'medium');

        $prompt = "Generate {$count} {$difficulty} study flashcards on \"{$topic}\". "
            .'Each card has a concise question/term on the front and a clear, correct answer on the back. '
            .'Respond ONLY with JSON: {"cards":[{"front","back"}]}';

        $parsed = $this->tryJson($this->provider->chat([
            ['role' => 'system', 'content' => 'You are an expert tutor who writes concise, accurate study flashcards.'],
            ['role' => 'user', 'content' => $prompt],
        ])->content);

        $cards = $parsed['cards'] ?? $this->synthesizeFlashcards($topic, $count);

        return collect($cards)
            ->map(fn ($card) => [
                'front' => trim((string) ($card['front'] ?? '')),
                'back' => trim((string) ($card['back'] ?? '')),
            ])
            ->filter(fn (array $card) => $card['front'] !== '' && $card['back'] !== '')
            ->take($count)
            ->values()
            ->all();
    }

    /**
     * Deterministic fallback flashcards when no LLM is configured.
     *
     * @return array<int, array{front: string, back: string}>
     */
    private function synthesizeFlashcards(string $topic, int $count): array
    {
        $cards = [];
        for ($i = 1; $i <= $count; $i++) {
            $cards[] = [
                'front' => "Key concept #{$i} of {$topic}",
                'back' => "Review your course materials on {$topic} to complete this card.",
            ];
        }

        return $cards;
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

    /**
     * Draft a full grade for a submission: per-rubric-criterion scores with
     * one-line justifications, a suggested overall grade, and feedback.
     * Faculty review and edit before saving — nothing is released
     * automatically. Falls back to a clearly-labelled heuristic draft when no
     * LLM responds usefully.
     *
     * @param  array{assignmentTitle: string, totalPoints: mixed, criteria: array<int, array{name: string, points: float}>, submissionText: string}  $params
     * @return array{criteria: array<int, array{name: string, points: float, score: float, justification: string}>, suggestedGrade: ?float, feedback: string, strengths: array<int, string>, improvements: array<int, string>, source: string}
     */
    public function draftRubricGrade(array $params): array
    {
        $title = trim((string) ($params['assignmentTitle'] ?? 'the assignment'));
        $totalPoints = (float) ($params['totalPoints'] ?? 0);
        $criteria = array_values((array) ($params['criteria'] ?? []));
        $text = trim((string) ($params['submissionText'] ?? ''));

        $parsed = $text === '' ? null : $this->tryJson($this->provider->chat([
            [
                'role' => 'system',
                'content' => 'You are a fair, consistent university grader. Score strictly against the rubric; '
                    .'justify each score in one sentence grounded in the submission. Never exceed the maximum points.',
            ],
            ['role' => 'user', 'content' => $this->rubricGradePrompt($title, $totalPoints, $criteria, $text)],
        ])->content);

        $scored = $this->scoreCriteria($criteria, $parsed, $text);
        $fromAi = $parsed !== null && ($criteria === [] ? isset($parsed['score']) : ! empty($parsed['criteria']));

        $suggested = $criteria !== []
            ? round(array_sum(array_column($scored, 'score')), 1)
            : $this->overallScore($parsed, $totalPoints, $title, $text);

        $grade = $suggested;

        return [
            'criteria' => $scored,
            'suggestedGrade' => $suggested,
            'feedback' => trim((string) ($parsed['feedback'] ?? $this->fallbackFeedback($title, $grade, $totalPoints))),
            'strengths' => array_values((array) ($parsed['strengths'] ?? $this->fallbackStrengths($grade, $totalPoints))),
            'improvements' => array_values((array) ($parsed['improvements'] ?? $this->fallbackImprovements($grade, $totalPoints))),
            'source' => $fromAi ? 'ai' : 'heuristic',
        ];
    }

    /**
     * @param  array<int, array{name: string, points: float}>  $criteria
     */
    private function rubricGradePrompt(string $title, float $totalPoints, array $criteria, string $text): string
    {
        if ($criteria !== []) {
            $rubricLines = collect($criteria)
                ->map(fn (array $criterion) => "- \"{$criterion['name']}\" (max {$criterion['points']} points)")
                ->implode("\n");

            return "Grade this student submission for \"{$title}\" against the rubric below.\n\n"
                ."Rubric:\n{$rubricLines}\n\n"
                ."Submission:\n\"\"\"\n{$text}\n\"\"\"\n\n"
                .'Respond ONLY with JSON: {"criteria":[{"name":"exact rubric name","score":number,'
                .'"justification":"one sentence"}],"feedback":"2-4 sentences for the student",'
                .'"strengths":["..."],"improvements":["..."]}';
        }

        return "Grade this student submission for \"{$title}\" out of {$totalPoints} points.\n\n"
            ."Submission:\n\"\"\"\n{$text}\n\"\"\"\n\n"
            .'Respond ONLY with JSON: {"score":number,"feedback":"2-4 sentences for the student",'
            .'"strengths":["..."],"improvements":["..."]}';
    }

    /**
     * Marry the model's criterion scores back to the rubric, clamped to each
     * criterion's maximum; criteria the model skipped get heuristic scores.
     *
     * @param  array<int, array{name: string, points: float}>  $criteria
     * @param  array<string, mixed>|null  $parsed
     * @return array<int, array{name: string, points: float, score: float, justification: string}>
     */
    private function scoreCriteria(array $criteria, ?array $parsed, string $text): array
    {
        $byName = collect((array) ($parsed['criteria'] ?? []))
            ->filter(fn ($row) => is_array($row) && isset($row['name']))
            ->keyBy(fn (array $row) => mb_strtolower(trim((string) $row['name'])));

        return array_map(function (array $criterion) use ($byName, $text): array {
            $match = $byName->get(mb_strtolower(trim($criterion['name'])));

            if ($match !== null && is_numeric($match['score'] ?? null)) {
                return [
                    'name' => $criterion['name'],
                    'points' => $criterion['points'],
                    'score' => round(max(0.0, min((float) $criterion['points'], (float) $match['score'])), 1),
                    'justification' => trim((string) ($match['justification'] ?? '')),
                ];
            }

            return [
                'name' => $criterion['name'],
                'points' => $criterion['points'],
                'score' => $this->heuristicScore((float) $criterion['points'], $criterion['name'], $text),
                'justification' => 'Heuristic draft (no AI response for this criterion) — verify against the submission.',
            ];
        }, $criteria);
    }

    /**
     * Summarize anonymous course-feedback comments into themes for faculty.
     * Comments arrive already anonymized; the summary never references
     * individual respondents. Deterministic fallback when no LLM responds.
     *
     * @param  array<int, string>  $comments
     * @param  array<int, int>  $ratingDistribution  rating (1-5) → count
     * @return array{summary: string, positives: array<int, string>, concerns: array<int, string>, suggestions: array<int, string>, source: string}
     */
    public function summarizeFeedback(string $courseLabel, array $comments, array $ratingDistribution): array
    {
        $comments = array_values(array_filter(array_map('trim', $comments)));

        $parsed = null;
        if ($comments !== []) {
            $commentBlock = collect($comments)
                ->take(60)
                ->map(fn (string $comment) => '- '.mb_substr($comment, 0, 400))
                ->implode("\n");
            $ratingLine = collect($ratingDistribution)
                ->map(fn (int $count, int $rating) => "{$rating}★: {$count}")
                ->implode(', ');

            // The response-format spec lives in the SYSTEM message: the mock
            // provider echoes the user question back, and a valid-JSON template
            // there would be mistaken for a real model answer by tryJson().
            $parsed = $this->tryJson($this->provider->chat([
                [
                    'role' => 'system',
                    'content' => 'You summarize anonymous student course feedback for the instructor. Group recurring '
                        .'themes; never quote in a way that could identify a student; stay balanced and specific. '
                        .'Respond ONLY with JSON of this shape: {"summary":"2-3 sentences","positives":["theme"],'
                        .'"concerns":["theme"],"suggestions":["actionable step for the instructor"]}',
                ],
                [
                    'role' => 'user',
                    'content' => "Course: {$courseLabel}. Rating distribution: {$ratingLine}.\n\n"
                        ."Anonymous comments:\n{$commentBlock}",
                ],
            ])->content);
        }

        if (is_array($parsed) && isset($parsed['summary'])) {
            return [
                'summary' => trim((string) $parsed['summary']),
                'positives' => array_values((array) ($parsed['positives'] ?? [])),
                'concerns' => array_values((array) ($parsed['concerns'] ?? [])),
                'suggestions' => array_values((array) ($parsed['suggestions'] ?? [])),
                'source' => 'ai',
            ];
        }

        return $this->fallbackFeedbackSummary($comments, $ratingDistribution);
    }

    /**
     * @param  array<int, string>  $comments
     * @param  array<int, int>  $ratingDistribution
     * @return array{summary: string, positives: array<int, string>, concerns: array<int, string>, suggestions: array<int, string>, source: string}
     */
    private function fallbackFeedbackSummary(array $comments, array $ratingDistribution): array
    {
        $total = array_sum($ratingDistribution);
        $average = $total > 0
            ? round(collect($ratingDistribution)->map(fn (int $count, int $rating) => $rating * $count)->sum() / $total, 1)
            : null;
        $positiveShare = $total > 0
            ? (($ratingDistribution[4] ?? 0) + ($ratingDistribution[5] ?? 0)) / $total
            : 0;

        $summary = $total === 0
            ? 'No responses yet.'
            : "{$total} response(s) with an average rating of {$average}/5"
                .($comments !== [] ? ' and '.count($comments).' written comment(s)' : '')
                .'. Configure an AI provider for a thematic summary — the raw comments are listed below.';

        return [
            'summary' => $summary,
            'positives' => $positiveShare >= 0.5 ? ['Most respondents rated the course 4★ or higher'] : [],
            'concerns' => $positiveShare < 0.5 && $total > 0 ? ['Fewer than half of respondents rated the course 4★ or higher'] : [],
            'suggestions' => $comments !== [] ? ['Read the written comments below for specifics'] : [],
            'source' => 'heuristic',
        ];
    }

    /**
     * @param  array<string, mixed>|null  $parsed
     */
    private function overallScore(?array $parsed, float $totalPoints, string $title, string $text): ?float
    {
        if (is_numeric($parsed['score'] ?? null)) {
            return round(max(0.0, min($totalPoints, (float) $parsed['score'])), 1);
        }

        return $totalPoints > 0 ? $this->heuristicScore($totalPoints, $title, $text) : null;
    }

    /**
     * Keyless draft score: submission length plus keyword coverage of the
     * criterion/assignment wording. Deliberately mid-range and clearly
     * labelled as heuristic in the justification.
     */
    private function heuristicScore(float $maxPoints, string $keywords, string $text): float
    {
        $words = str_word_count($text);
        $lengthFactor = min(1.0, $words / 150);

        preg_match_all('/[a-z]{4,}/', mb_strtolower($keywords), $matches);
        $terms = array_unique($matches[0]);
        $lower = mb_strtolower($text);
        $hits = count(array_filter($terms, fn (string $term) => str_contains($lower, $term)));
        $coverage = $terms === [] ? 0.5 : $hits / count($terms);

        return round($maxPoints * (0.4 + 0.3 * $lengthFactor + 0.3 * $coverage) * 2) / 2;
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
     * Deterministic fallback when no LLM is configured — produces a question for
     * each requested type so every pattern renders end to end.
     *
     * @param  array<int, string>  $types
     * @return array<int, array<string, mixed>>
     */
    private function synthesizeQuestions(string $topic, int $count, array $types = ['multiple-choice']): array
    {
        $types = $types === [] ? ['multiple-choice'] : $types;

        $questions = [];
        for ($i = 0; $i < $count; $i++) {
            $questions[] = $this->synthesizeQuestion($topic, $types[$i % count($types)]);
        }

        return $questions;
    }

    /**
     * @return array<string, mixed>
     */
    private function synthesizeQuestion(string $topic, string $type): array
    {
        return match ($type) {
            'true-false' => [
                'type' => 'true-false',
                'question' => "True or False: {$topic} is a core concept covered in this course.",
                'answer' => true,
                'explanation' => "This reflects the fundamentals of {$topic}.",
            ],
            'short-answer' => [
                'type' => 'short-answer',
                'question' => "In one or two sentences, define {$topic}.",
                'answer' => "A concise definition of {$topic} and its purpose.",
                'explanation' => "Look for the key idea behind {$topic}.",
            ],
            'essay' => [
                'type' => 'essay',
                'question' => "Discuss the importance of {$topic} and give an example of its application.",
                'answer' => "A structured response covering the definition, importance, and an example of {$topic}.",
                'explanation' => 'Reward depth of analysis and relevant examples.',
            ],
            'fill-in-blank' => [
                'type' => 'fill-in-blank',
                'question' => "Fill in the blank: ____ is a key principle of {$topic}.",
                'answer' => "A correct principle of {$topic}",
                'explanation' => "Any accurate principle of {$topic} is acceptable.",
            ],
            'matching' => [
                'type' => 'matching',
                'question' => "Match each term to its description in the context of {$topic}.",
                'answer' => "Pair each {$topic} term with its correct description.",
                'explanation' => 'Verify each pairing against the course notes.',
            ],
            default => [
                'type' => 'multiple-choice',
                'question' => "Which of the following best describes {$topic}?",
                'options' => [
                    "A correct concept of {$topic}",
                    'A plausible but incorrect statement',
                    'An unrelated concept',
                    'None of the above',
                ],
                'answer' => "A correct concept of {$topic}",
                'explanation' => "This relates to the fundamentals of {$topic} covered in lectures.",
            ],
        };
    }
}
