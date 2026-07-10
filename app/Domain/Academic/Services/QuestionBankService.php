<?php

declare(strict_types=1);

namespace App\Domain\Academic\Services;

use App\Domain\User\Models\User;
use App\Models\ClassTest;
use App\Models\PracticeQuiz;
use App\Models\QuestionBankItem;
use App\Models\Section;
use Illuminate\Support\Collection;

/**
 * Per-course question bank for faculty: curate reusable MCQ/true-false
 * questions manually or by importing them from existing class tests, spin a
 * draft class test out of a selection, and let enrolled students self-quiz
 * on the bank (deterministic — no AI involved).
 */
class QuestionBankService
{
    public function __construct(private readonly ClassTestService $classTests) {}

    /**
     * Ids of the courses this faculty member teaches a section of.
     *
     * @return Collection<int, int>
     */
    public function teachableCourseIds(User $faculty): Collection
    {
        return Section::where('faculty_id', $faculty->id)
            ->pluck('course_id')
            ->unique()
            ->values();
    }

    /**
     * Bank items across the faculty member's courses, newest first.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function itemsFor(User $faculty, ?int $courseId = null): Collection
    {
        $courseIds = $this->teachableCourseIds($faculty);
        if ($courseId !== null) {
            abort_unless($courseIds->contains($courseId), 403);
            $courseIds = collect([$courseId]);
        }

        return QuestionBankItem::query()
            ->whereIn('course_id', $courseIds)
            ->with('course:id,code')
            ->latest()
            ->get()
            ->map(fn (QuestionBankItem $item) => $this->present($item))
            ->values();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function store(User $faculty, array $data): QuestionBankItem
    {
        abort_unless($this->teachableCourseIds($faculty)->contains((int) $data['course_id']), 403);

        return QuestionBankItem::create([
            'course_id' => (int) $data['course_id'],
            'created_by' => $faculty->id,
            'type' => $data['type'],
            'question_text' => $data['question_text'],
            'options' => $data['type'] === 'mcq' ? array_values($data['options'] ?? []) : null,
            'correct_answer' => (string) $data['correct_answer'],
            'marks' => max(1, min(100, (int) ($data['marks'] ?? 1))),
            'topic' => $data['topic'] ?? null,
            'difficulty' => $data['difficulty'] ?? 'medium',
        ]);
    }

    public function delete(User $faculty, QuestionBankItem $item): void
    {
        abort_unless($this->teachableCourseIds($faculty)->contains($item->course_id), 403);

        $item->delete();
    }

    /**
     * Copy a class test's questions into its course's bank, skipping items
     * with identical text already in the bank. Returns how many were added.
     */
    public function importFromTest(User $faculty, ClassTest $test): int
    {
        abort_unless($this->teachableCourseIds($faculty)->contains($test->course_id), 403);

        $existing = QuestionBankItem::where('course_id', $test->course_id)
            ->pluck('question_text')
            ->map(fn (string $text) => mb_strtolower(trim($text)))
            ->flip();

        $imported = 0;
        foreach ($test->questions as $question) {
            if ($existing->has(mb_strtolower(trim($question->question_text)))) {
                continue;
            }

            QuestionBankItem::create([
                'course_id' => $test->course_id,
                'created_by' => $faculty->id,
                'type' => $question->type,
                'question_text' => $question->question_text,
                'options' => $question->options,
                'correct_answer' => $question->correct_answer,
                'marks' => $question->marks,
                'topic' => $test->title,
                'difficulty' => 'medium',
            ]);
            $imported++;
        }

        return $imported;
    }

    /**
     * Spin a DRAFT class test out of selected bank items (the faculty member
     * reviews and publishes it via the normal class-test flow).
     *
     * @param  array<int, int>  $itemIds
     */
    public function createDraftTest(User $faculty, Section $section, string $title, array $itemIds): ClassTest
    {
        abort_unless($section->faculty_id === $faculty->id, 403);

        $items = QuestionBankItem::whereIn('id', $itemIds)
            ->where('course_id', $section->course_id)
            ->get();
        abort_if($items->isEmpty(), 422, 'Select at least one bank question for this section\'s course.');

        return $this->classTests->create([
            'section_id' => $section->id,
            'title' => $title,
            'duration_minutes' => max(5, $items->count() * 2),
            'status' => 'draft',
            'questions' => $items->map(fn (QuestionBankItem $item) => [
                'type' => $item->type,
                'question_text' => $item->question_text,
                'options' => $item->options ?? [],
                'correct_answer' => $item->correct_answer,
                'marks' => $item->marks,
            ])->values()->all(),
        ], $faculty);
    }

    /**
     * Student self-quiz from the bank: sample items from an enrolled course
     * and persist them as a practice quiz (same take/grade flow as AI-made
     * quizzes; answers stay server-side).
     */
    public function practiceQuizFromBank(User $student, int $courseId, int $count = 10): PracticeQuiz
    {
        abort_unless(
            $student->enrolledCourses()
                ->wherePivotNotIn('status', ['pending'])
                ->where('courses.id', $courseId)
                ->exists(),
            403,
            'You can only practice from courses you are enrolled in.',
        );

        $items = QuestionBankItem::where('course_id', $courseId)
            ->inRandomOrder()
            ->limit(max(1, min(20, $count)))
            ->with('course:id,code')
            ->get();
        abort_if($items->isEmpty(), 422, 'This course has no question-bank items yet.');

        $courseCode = $items->first()->course?->code ?? 'Course';

        return PracticeQuiz::create([
            'user_id' => $student->id,
            'course_id' => $courseId,
            'title' => "{$courseCode} question bank practice",
            'topic' => "{$courseCode} question bank",
            'difficulty' => 'medium',
            'questions' => $items->values()->map(fn (QuestionBankItem $item, int $index) => $this->toPracticeQuestion($item, $index + 1))->all(),
        ]);
    }

    /**
     * Bank items use the class-test wire shape ({key,text} options, key or
     * true/false answers); practice quizzes grade against option TEXT — map
     * between the two here.
     *
     * @return array<string, mixed>
     */
    private function toPracticeQuestion(QuestionBankItem $item, int $id): array
    {
        if ($item->type === 'true_false') {
            return [
                'id' => $id,
                'type' => 'true-false',
                'question' => $item->question_text,
                'options' => [],
                'answer' => strtolower($item->correct_answer) === 'true',
                'explanation' => null,
            ];
        }

        $options = collect($item->options ?? []);

        return [
            'id' => $id,
            'type' => 'multiple-choice',
            'question' => $item->question_text,
            'options' => $options->pluck('text')->values()->all(),
            'answer' => (string) ($options->firstWhere('key', $item->correct_answer)['text'] ?? ''),
            'explanation' => null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function present(QuestionBankItem $item): array
    {
        return [
            'id' => $item->id,
            'courseId' => $item->course_id,
            'course' => $item->course?->code,
            'type' => $item->type,
            'question' => $item->question_text,
            'options' => $item->options ?? [],
            'correctAnswer' => $item->correct_answer,
            'marks' => $item->marks,
            'topic' => $item->topic,
            'difficulty' => $item->difficulty,
            'createdAt' => $item->created_at?->diffForHumans(),
        ];
    }
}
