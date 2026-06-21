<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\User\Models\User;
use App\Models\ClassTest;
use App\Models\Section;
use App\Models\Term;
use Illuminate\Database\Seeder;

/**
 * Seeds faculty-authored class tests, mirroring how a real faculty member creates
 * them in the Faculty panel (see {@see \App\Domain\Academic\Services\ClassTestService}).
 *
 * Isolation is STRUCTURAL, not enforced by ad-hoc filtering. A ClassTest binds to a
 * single Section, and a Section is exactly one offering of one Course (the subject)
 * in one department + semester. Students only ever see tests for sections they are
 * enrolled in (ClassTestService::studentList → published + whereIn enrolledSectionIds).
 * Therefore attaching a test to a section delivers it to precisely that
 *
 *     Department → Semester → Section → Subject (Course) → enrolled Students
 *
 * chain — never to another department, semester or section. No randomness is used:
 * every test/question is a deterministic function of the section and its index.
 *
 * Volume guarantee — "every student gets at least 20 class tests": each student in a
 * (department, semester) bucket is enrolled in C courses (one section each). If each
 * of a student's sections carries T tests, the student receives C × T tests. We size
 * T per section as ceil(MIN_TESTS_PER_STUDENT / minEnrolledCount) (with a realistic
 * floor), where minEnrolledCount is the smallest enrolled-section count among that
 * section's students. Since a student enrolled in k sections has every one of those
 * sections sized to T ≥ ceil(20 / k), their total is k × ceil(20 / k) ≥ 20.
 */
class ClassTestSeeder extends Seeder
{
    /** Floor on a student's total assigned tests; drives per-section test counts. */
    private const MIN_TESTS_PER_STUDENT = 20;

    /** Minimum tests per section regardless of load, so even heavy semesters look realistic. */
    private const MIN_TESTS_PER_SECTION = 4;

    public function run(): void
    {
        $this->command->info('Seeding class tests...');

        $term = Term::currentTerm();

        if (! $term) {
            $this->command->warn('   No current term; run TermSeeder first.');

            return;
        }

        // Current-term sections that actually have an enrolled roster. A test on an
        // empty section would reach nobody, so we skip those (e.g. CS301 section B).
        $sections = Section::query()
            ->where('term_id', $term->id)
            ->whereHas('students', fn ($query) => $query->where('course_user.status', 'enrolled'))
            ->with([
                'course.department',
                'faculty',
                'students' => fn ($query) => $query->wherePivot('status', 'enrolled'),
            ])
            ->get();

        if ($sections->isEmpty()) {
            $this->command->warn('   No enrolled sections in the current term; run SectionSeeder + EnrollmentSeeder first.');

            return;
        }

        $enrolledCount = $this->enrolledSectionCountByStudent($sections);

        $testsCreated = 0;
        $sectionsTouched = 0;

        foreach ($sections as $section) {
            if (! $section->course) {
                continue;
            }

            $perSection = $this->testsForSection($section, $enrolledCount);
            $created = $this->seedSectionTests($section, $perSection);

            $testsCreated += $created;
            if ($created > 0) {
                $sectionsTouched++;
            }
        }

        $this->command->info("   ✓ Class tests seeded ({$testsCreated} tests across {$sectionsTouched} sections)");
    }

    /**
     * How many enrolled sections each student belongs to, counted over exactly the
     * sections we are about to seed — so it matches what the student will actually see.
     *
     * @param  \Illuminate\Support\Collection<int, Section>  $sections
     * @return array<int, int> user_id => enrolled-section count
     */
    private function enrolledSectionCountByStudent($sections): array
    {
        $counts = [];

        foreach ($sections as $section) {
            foreach ($section->students as $student) {
                $counts[$student->id] = ($counts[$student->id] ?? 0) + 1;
            }
        }

        return $counts;
    }

    /**
     * Per-section test count that, summed across each student's sections, clears the
     * 20-test floor. Driven by the lightest-loaded student in the section.
     *
     * @param  array<int, int>  $enrolledCount
     */
    private function testsForSection(Section $section, array $enrolledCount): int
    {
        $loads = $section->students
            ->map(fn (User $student) => $enrolledCount[$student->id] ?? 1)
            ->filter()
            ->all();

        $minLoad = $loads === [] ? 1 : min($loads);

        $needed = (int) ceil(self::MIN_TESTS_PER_STUDENT / max(1, $minLoad));

        return max(self::MIN_TESTS_PER_SECTION, $needed);
    }

    /**
     * Create `count` class tests for one section, each a distinct, realistic "kind"
     * of test, published and ready. Idempotent: tests are keyed by (section, title),
     * so re-running the seeder never duplicates them.
     */
    private function seedSectionTests(Section $section, int $count): int
    {
        $facultyId = $section->faculty_id ?? $section->course?->faculty_id;
        $created = 0;

        for ($index = 0; $index < $count; $index++) {
            $template = $this->testTemplate($index);
            $title = "{$template['kind']} {$template['serial']} — {$section->course->code}";

            $existing = ClassTest::where('section_id', $section->id)
                ->where('title', $title)
                ->first();

            if ($existing) {
                continue;
            }

            $questions = $this->buildQuestions($section, $template);
            $totalMarks = array_sum(array_column($questions, 'marks'));

            $test = ClassTest::create([
                'course_id' => $section->course_id,
                'section_id' => $section->id,
                'title' => $title,
                'description' => $template['description'],
                'duration_minutes' => $template['duration'],
                'total_marks' => $totalMarks,
                'pass_marks' => (int) ceil($totalMarks * 0.4),
                'status' => 'published',
                'available_from' => now()->subDays($template['opensDaysAgo']),
                'available_until' => now()->addDays($template['closesInDays']),
                'shuffle_questions' => $template['shuffle'],
                'max_warnings' => $template['maxWarnings'],
                'created_by' => $facultyId,
            ]);

            $test->questions()->createMany($questions);

            $created++;
        }

        return $created;
    }

    /**
     * A rotating catalogue of realistic test "kinds" (quiz, pop quiz, chapter test,
     * …), each with its own duration, length and question-type mix. The serial number
     * lets the same kind recur (Quiz 1, Quiz 2, …) within a section.
     *
     * @return array<string, mixed>
     */
    private function testTemplate(int $index): array
    {
        $kinds = [
            ['kind' => 'Quiz', 'duration' => 15, 'questions' => 5, 'mix' => 'mcq', 'marks' => 1, 'shuffle' => true, 'maxWarnings' => 2, 'opensDaysAgo' => 21, 'closesInDays' => 30, 'description' => 'Short multiple-choice quiz covering recent lecture material.'],
            ['kind' => 'Pop Quiz', 'duration' => 10, 'questions' => 5, 'mix' => 'true_false', 'marks' => 1, 'shuffle' => false, 'maxWarnings' => 1, 'opensDaysAgo' => 18, 'closesInDays' => 30, 'description' => 'Quick true/false check on the core concepts of this week.'],
            ['kind' => 'Chapter Test', 'duration' => 25, 'questions' => 8, 'mix' => 'mixed', 'marks' => 2, 'shuffle' => true, 'maxWarnings' => 3, 'opensDaysAgo' => 14, 'closesInDays' => 30, 'description' => 'End-of-chapter assessment with mixed question types.'],
            ['kind' => 'Weekly Assessment', 'duration' => 20, 'questions' => 6, 'mix' => 'mcq', 'marks' => 1, 'shuffle' => true, 'maxWarnings' => 2, 'opensDaysAgo' => 10, 'closesInDays' => 30, 'description' => 'Weekly graded assessment to track ongoing understanding.'],
            ['kind' => 'Concept Check', 'duration' => 12, 'questions' => 6, 'mix' => 'true_false', 'marks' => 1, 'shuffle' => false, 'maxWarnings' => 1, 'opensDaysAgo' => 7, 'closesInDays' => 30, 'description' => 'Rapid true/false review of fundamental definitions.'],
            ['kind' => 'Surprise Test', 'duration' => 10, 'questions' => 5, 'mix' => 'mixed', 'marks' => 1, 'shuffle' => true, 'maxWarnings' => 2, 'opensDaysAgo' => 5, 'closesInDays' => 30, 'description' => 'Unannounced short test on the latest topics.'],
            ['kind' => 'Mid-Unit Quiz', 'duration' => 30, 'questions' => 10, 'mix' => 'mixed', 'marks' => 2, 'shuffle' => true, 'maxWarnings' => 3, 'opensDaysAgo' => 3, 'closesInDays' => 30, 'description' => 'Comprehensive mid-unit quiz spanning several chapters.'],
        ];

        $base = $kinds[$index % count($kinds)];
        $base['serial'] = intdiv($index, count($kinds)) + 1;

        return $base;
    }

    /**
     * Build the deterministic question set for a test. MCQ answers cycle A–D and
     * true/false answers alternate, so the data is varied yet reproducible. Shapes
     * exactly match what ClassTestService stores and the grader expects:
     *   - mcq: options [{key,text}] (A–D), correct_answer = an option key
     *   - true_false: options null, correct_answer = 'true' | 'false'
     *
     * @param  array<string, mixed>  $template
     * @return array<int, array<string, mixed>>
     */
    private function buildQuestions(Section $section, array $template): array
    {
        $topics = [
            'fundamental concepts', 'core principles', 'key definitions',
            'problem-solving techniques', 'analysis methods', 'design patterns',
            'theoretical foundations', 'practical applications', 'common pitfalls',
            'case studies',
        ];

        $courseName = $section->course->name;
        $courseCode = $section->course->code;
        $questions = [];

        for ($i = 0; $i < $template['questions']; $i++) {
            $topic = $topics[$i % count($topics)];

            $type = match ($template['mix']) {
                'mcq' => 'mcq',
                'true_false' => 'true_false',
                default => $i % 2 === 0 ? 'mcq' : 'true_false',
            };

            if ($type === 'mcq') {
                $questions[] = $this->mcqQuestion($courseCode, $courseName, $topic, $i, (int) $template['marks']);
            } else {
                $questions[] = $this->trueFalseQuestion($courseCode, $courseName, $topic, $i, (int) $template['marks']);
            }
        }

        return $questions;
    }

    /**
     * @return array<string, mixed>
     */
    private function mcqQuestion(string $code, string $name, string $topic, int $index, int $marks): array
    {
        $keys = ['A', 'B', 'C', 'D'];

        $options = [];
        foreach ($keys as $position => $key) {
            $options[] = [
                'key' => $key,
                'text' => "Interpretation {$key} of {$topic} in {$name}",
            ];
        }

        return [
            'type' => 'mcq',
            'question_text' => "[{$code}] Q".($index + 1).": Which option best characterises {$topic} as covered in this course?",
            'options' => $options,
            'correct_answer' => $keys[$index % count($keys)],
            'marks' => $marks,
            'position' => $index,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function trueFalseQuestion(string $code, string $name, string $topic, int $index, int $marks): array
    {
        return [
            'type' => 'true_false',
            'question_text' => "[{$code}] Q".($index + 1).": In {$name}, {$topic} is a topic addressed within this course.",
            'options' => null,
            'correct_answer' => $index % 2 === 0 ? 'true' : 'false',
            'marks' => $marks,
            'position' => $index,
        ];
    }
}
