<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\User\Models\User;
use App\Models\Assignment;
use App\Models\ClassTest;
use App\Models\ClassTestAttempt;
use App\Models\ClassTestSnapshot;
use App\Models\Course;
use App\Models\CourseFeedback;
use App\Models\OfficeHourSlot;
use App\Models\PeerReview;
use App\Models\PracticeQuiz;
use App\Models\QuestionBankItem;
use App\Models\Section;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Backfills demo data for the recently-shipped feature waves that had no seeder
 * coverage, so the three demo accounts (student / faculty / admin) can showcase
 * each feature end-to-end:
 *
 *   - Practice quizzes + graded attempts (feeds concept mastery + analytics)
 *   - Per-course question bank
 *   - Anonymous peer review on an assignment
 *   - Anonymous mid-semester course feedback (window open)
 *   - Bookable faculty office-hours slots (open + booked)
 *   - Camera-proctoring snapshot evidence on the student's class-test attempts
 *   - Course prerequisites + a section waitlist
 *
 * Runs LAST (after DemoCourseRosterSeeder + ClassTestAttemptSeeder) so the demo
 * courses have a cohort and the student has submitted attempts to attach to.
 * Every write is idempotent, so re-running never duplicates.
 */
class DemoFeatureShowcaseSeeder extends Seeder
{
    /** 1×1 grey JPEG used when GD is unavailable to render a placeholder frame. */
    private const FALLBACK_JPEG = '/9j/4AAQSkZJRgABAQEAYABgAAD/2wBDAAgGBgcGBQgHBwcJCQgKDBQNDAsLDBkSEw8UHRof'
        .'Hh0aHBwgJC4nICIsIxwcKDcpLDAxNDQ0Hyc5PTgyPC4zNDL/2wBDAQkJCQwLDBgNDRgyIRwh'
        .'MjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjL/wAAR'
        .'CAABAAEDASIAAhEBAxEB/8QAHwAAAQUBAQEBAQEAAAAAAAAAAAECAwQFBgcICQoL/8QAtRAA'
        .'AgEDAwIEAwUFBAQAAAF9AQIDAAQRBRIhMUEGE1FhByJxFDKBkaEII0KxwRVS0fAkM2JyggkK'
        .'FhcYGRolJicoKSo0NTY3ODk6Q0RFRkdISUpTVFVWV1hZWmNkZWZnaGlqc3R1dnd4eXqDhIWG'
        .'h4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uHi4+Tl'
        .'5ufo6erx8vP09fb3+Pn6/8QAHwEAAwEBAQEBAQEBAQAAAAAAAAECAwQFBgcICQoL/8QAtREA'
        .'AgECBAQDBAcFBAQAAQJ3AAECAxEEBSExBhJBUQdhcRMiMoEIFEKRobHBCSMzUvAVYnLRChYk'
        .'NOEl8RcYGRomJygpKjU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6goOE'
        .'hYaHiImKkpOUlZaXmJmaoqOkpaanqKmqsrO0tba3uLm6wsPExcbHyMnK0tPU1dbX2Nna4uPk'
        .'5ebn6Onq8vP09fb3+Pn6/9oADAMBAAIRAxEAPwD3+iiigD/2Q==';

    public function run(): void
    {
        $this->command->info('Seeding demo showcase data for recent features...');

        $student = User::where('email', 'student@university.edu')->first();
        $smith = User::where('email', 'prof.smith@university.edu')->first();
        $jones = User::where('email', 'prof.jones@university.edu')->first();

        if (! $student || ! $smith) {
            $this->command->warn('   Demo student/faculty missing; run RBACSeeder + AcademicSeeder first.');

            return;
        }

        $this->seedPracticeQuizzes($student);
        $this->seedQuestionBank($smith);
        $this->seedPeerReview($student);
        $this->seedCourseFeedback($student);
        $this->seedOfficeHours($student, $smith, $jones);
        $this->seedProctoringSnapshots($student);
        $this->seedPrerequisitesAndWaitlist($student);

        $this->command->info('   ✓ Demo showcase data seeded');
    }

    /**
     * Practice quizzes with a graded attempt each, keyed to the demo student's
     * enrolled courses so the concept-mastery map and learning analytics light up.
     */
    private function seedPracticeQuizzes(User $student): void
    {
        $quizzes = [
            [
                'code' => 'CS301', 'topic' => 'Data Structures & Algorithms', 'difficulty' => 'medium',
                'score' => 4,
                'questions' => [
                    ['id' => 1, 'type' => 'multiple-choice', 'question' => 'What is the average-case time complexity of a binary search?', 'options' => ['O(n)', 'O(log n)', 'O(n log n)', 'O(1)'], 'answer' => 'O(log n)', 'explanation' => 'Binary search halves the search space each step.'],
                    ['id' => 2, 'type' => 'multiple-choice', 'question' => 'Which structure gives O(1) average insert and lookup?', 'options' => ['Array', 'Hash table', 'Binary tree', 'Linked list'], 'answer' => 'Hash table', 'explanation' => 'Hashing distributes keys into buckets.'],
                    ['id' => 3, 'type' => 'true-false', 'question' => 'A stack follows First-In-First-Out ordering.', 'answer' => 'false', 'explanation' => 'A stack is LIFO; a queue is FIFO.'],
                    ['id' => 4, 'type' => 'multiple-choice', 'question' => 'Which traversal visits root, then left, then right?', 'options' => ['Inorder', 'Preorder', 'Postorder', 'Level-order'], 'answer' => 'Preorder', 'explanation' => 'Preorder = root → left → right.'],
                    ['id' => 5, 'type' => 'true-false', 'question' => 'Merge sort has O(n log n) worst-case complexity.', 'answer' => 'true', 'explanation' => 'Merge sort is always O(n log n).'],
                ],
            ],
            [
                'code' => 'CS310', 'topic' => 'Database Systems', 'difficulty' => 'medium',
                'score' => 3,
                'questions' => [
                    ['id' => 1, 'type' => 'multiple-choice', 'question' => 'Which normal form removes transitive dependencies?', 'options' => ['1NF', '2NF', '3NF', 'BCNF'], 'answer' => '3NF', 'explanation' => '3NF eliminates transitive dependencies on the key.'],
                    ['id' => 2, 'type' => 'true-false', 'question' => 'A primary key can contain NULL values.', 'answer' => 'false', 'explanation' => 'Primary keys must be unique and NOT NULL.'],
                    ['id' => 3, 'type' => 'multiple-choice', 'question' => 'Which SQL clause filters grouped rows?', 'options' => ['WHERE', 'HAVING', 'GROUP BY', 'ORDER BY'], 'answer' => 'HAVING', 'explanation' => 'HAVING filters after aggregation.'],
                    ['id' => 4, 'type' => 'multiple-choice', 'question' => 'What does ACID stand for in transactions?', 'options' => ['Atomicity, Consistency, Isolation, Durability', 'Access, Control, Index, Data', 'Atomic, Cached, Indexed, Durable', 'None of these'], 'answer' => 'Atomicity, Consistency, Isolation, Durability', 'explanation' => 'The four transaction guarantees.'],
                    ['id' => 5, 'type' => 'true-false', 'question' => 'An index always speeds up write operations.', 'answer' => 'false', 'explanation' => 'Indexes add overhead to writes.'],
                ],
            ],
            [
                'code' => 'CS305', 'topic' => 'Machine Learning Fundamentals', 'difficulty' => 'hard',
                'score' => 5,
                'questions' => [
                    ['id' => 1, 'type' => 'multiple-choice', 'question' => 'Which metric is best for an imbalanced classification problem?', 'options' => ['Accuracy', 'F1-score', 'Mean squared error', 'R-squared'], 'answer' => 'F1-score', 'explanation' => 'F1 balances precision and recall.'],
                    ['id' => 2, 'type' => 'true-false', 'question' => 'Overfitting means the model performs poorly on training data.', 'answer' => 'false', 'explanation' => 'Overfitting = great on training, poor on unseen data.'],
                    ['id' => 3, 'type' => 'multiple-choice', 'question' => 'Which technique reduces overfitting?', 'options' => ['Increasing model size', 'Regularization', 'Removing validation set', 'Training longer'], 'answer' => 'Regularization', 'explanation' => 'Regularization penalises complexity.'],
                    ['id' => 4, 'type' => 'multiple-choice', 'question' => 'Gradient descent minimises the...', 'options' => ['Learning rate', 'Loss function', 'Batch size', 'Number of epochs'], 'answer' => 'Loss function', 'explanation' => 'It steps down the loss gradient.'],
                    ['id' => 5, 'type' => 'true-false', 'question' => 'Supervised learning requires labelled data.', 'answer' => 'true', 'explanation' => 'Labels are the supervision signal.'],
                ],
            ],
        ];

        foreach ($quizzes as $data) {
            $course = Course::where('code', $data['code'])->first();

            $quiz = PracticeQuiz::firstOrCreate(
                ['user_id' => $student->id, 'title' => $data['topic'].' — Practice Quiz'],
                [
                    'course_id' => $course?->id,
                    'topic' => $data['topic'],
                    'difficulty' => $data['difficulty'],
                    'questions' => $data['questions'],
                ],
            );

            if ($quiz->attempts()->exists()) {
                continue;
            }

            // Build an answers map that reproduces the intended score: the first
            // `score` questions answered correctly, the rest left blank.
            $answers = [];
            foreach ($data['questions'] as $index => $question) {
                if ($index >= $data['score']) {
                    break;
                }
                $answers[$question['id']] = $question['type'] === 'true-false'
                    ? ($question['answer'] === 'true' ? 'True' : 'False')
                    : $question['answer'];
            }

            $quiz->attempts()->create([
                'answers' => $answers,
                'score' => $data['score'],
                'total' => count($data['questions']),
                'completed_at' => now()->subDays(2),
            ]);
        }
    }

    /**
     * A reusable question bank for the demo faculty's courses.
     */
    private function seedQuestionBank(User $faculty): void
    {
        // CS301 Section A is the canonical test fixture the feature tests build
        // their own bank/practice scenarios on, so it is left empty here.
        $banks = [
            'CS310' => [
                ['q' => 'Which key uniquely identifies a row?', 'opts' => ['Foreign key', 'Primary key', 'Candidate index', 'Composite view'], 'correct' => 'B', 'topic' => 'Relational Model', 'difficulty' => 'easy'],
                ['q' => 'A JOIN that returns only matching rows is a?', 'opts' => ['LEFT JOIN', 'INNER JOIN', 'FULL JOIN', 'CROSS JOIN'], 'correct' => 'B', 'topic' => 'SQL', 'difficulty' => 'medium'],
                ['q' => 'Which property guarantees transactions are all-or-nothing?', 'opts' => ['Isolation', 'Atomicity', 'Durability', 'Consistency'], 'correct' => 'B', 'topic' => 'Transactions', 'difficulty' => 'medium'],
            ],
            'CS305' => [
                ['q' => 'Which algorithm is used for classification?', 'opts' => ['K-means', 'Logistic regression', 'PCA', 'Apriori'], 'correct' => 'B', 'topic' => 'Supervised Learning', 'difficulty' => 'medium'],
                ['q' => 'Splitting data into train/test helps to?', 'opts' => ['Increase bias', 'Estimate generalisation', 'Speed training', 'Remove features'], 'correct' => 'B', 'topic' => 'Model Evaluation', 'difficulty' => 'easy'],
                ['q' => 'Which reduces dimensionality?', 'opts' => ['PCA', 'SVM', 'KNN', 'Naive Bayes'], 'correct' => 'A', 'topic' => 'Feature Engineering', 'difficulty' => 'hard'],
            ],
        ];

        foreach ($banks as $code => $items) {
            $course = Course::where('code', $code)->first();
            if (! $course) {
                continue;
            }

            foreach ($items as $item) {
                $options = [];
                foreach (['A', 'B', 'C', 'D'] as $i => $key) {
                    if (! isset($item['opts'][$i])) {
                        break;
                    }
                    $options[] = ['key' => $key, 'text' => $item['opts'][$i]];
                }

                QuestionBankItem::firstOrCreate(
                    ['course_id' => $course->id, 'question_text' => $item['q']],
                    [
                        'created_by' => $faculty->id,
                        'type' => 'mcq',
                        'options' => $options,
                        'correct_answer' => $item['correct'],
                        'marks' => 1,
                        'topic' => $item['topic'],
                        'difficulty' => $item['difficulty'],
                    ],
                );
            }
        }
    }

    /**
     * Anonymous peer review on the demo student's CS310 Assignment 1: classmates
     * review the student's work (received, completed), and the student is assigned
     * to review two classmates (pending tasks).
     */
    private function seedPeerReview(User $student): void
    {
        // Uses CS310 (not CS301 Section A, the shared test fixture) so the peer
        // review feature tests keep a clean slate.
        $course = Course::where('code', 'CS310')->first();
        if (! $course) {
            return;
        }

        $assignment = Assignment::where('course_id', $course->id)
            ->where('title', 'like', 'Assignment 1%')
            ->first();
        if (! $assignment) {
            return;
        }

        $assignment->update(['peer_review_enabled' => true]);

        $studentSubmission = $assignment->submissions()->where('user_id', $student->id)->first();
        if (! $studentSubmission) {
            $studentSubmission = $assignment->submissions()->create([
                'user_id' => $student->id,
                'content' => 'My submission for '.$assignment->title,
                'status' => 'submitted',
                'submitted_at' => now()->subDays(2),
            ]);
        }

        // Classmates enrolled in this section (excluding the demo student).
        $classmates = $this->classmatesFor($course, $student->id, 4);
        if ($classmates->isEmpty()) {
            return;
        }

        // Two classmates leave completed, anonymous reviews on the student's work.
        $reviewComments = [
            ['rating' => 4, 'comment' => 'Clear structure and correct approach — could add more edge-case handling.'],
            ['rating' => 5, 'comment' => 'Excellent explanation and well-documented solution. Nice work!'],
        ];
        foreach ($classmates->take(2)->values() as $index => $reviewer) {
            PeerReview::firstOrCreate(
                ['submission_id' => $studentSubmission->id, 'reviewer_id' => $reviewer->id],
                [
                    'assignment_id' => $assignment->id,
                    'rating' => $reviewComments[$index]['rating'],
                    'comments' => $reviewComments[$index]['comment'],
                    'completed_at' => now()->subDay(),
                ],
            );
        }

        // The demo student is assigned to review two classmates (pending tasks).
        foreach ($classmates->take(2)->values() as $reviewee) {
            $peerSubmission = $assignment->submissions()->firstOrCreate(
                ['user_id' => $reviewee->id],
                [
                    'content' => 'Peer submission for '.$assignment->title,
                    'status' => 'submitted',
                    'submitted_at' => now()->subDays(2),
                ],
            );

            PeerReview::firstOrCreate(
                ['submission_id' => $peerSubmission->id, 'reviewer_id' => $student->id],
                ['assignment_id' => $assignment->id, 'rating' => null, 'comments' => null, 'completed_at' => null],
            );
        }
    }

    /**
     * Open the mid-semester feedback window on two demo sections and seed a
     * handful of anonymous responses from the cohort (≥ MIN_RESPONSES so faculty
     * see aggregated results). The demo student's own slot is left open so they
     * can submit live.
     */
    private function seedCourseFeedback(User $student): void
    {
        $comments = [
            [5, 'Really well-paced lectures and helpful examples.'],
            [4, 'Good course overall; assignments are challenging but fair.'],
            [5, 'The instructor explains difficult topics clearly.'],
            [4, 'More practice problems in class would help.'],
            [3, 'Interesting material, though the workload is heavy.'],
        ];

        // Seed on the demo student's higher-numbered enrolled sections only.
        // CS301 Section A is the demo student's FIRST enrolled section, which the
        // CourseFeedback feature tests build their own scenario on — leaving it
        // empty keeps those tests deterministic.
        foreach (['CS305', 'CS310'] as $code) {
            $course = Course::where('code', $code)->first();
            if (! $course) {
                continue;
            }

            $section = $course->sections()
                ->where('label', 'A')
                ->whereHas('term', fn ($q) => $q->where('is_current', true))
                ->first();
            if (! $section) {
                continue;
            }

            $section->update(['feedback_open' => true]);

            $respondents = $this->classmatesFor($course, $student->id, 5);
            foreach ($respondents->values() as $index => $respondent) {
                [$rating, $comment] = $comments[$index % count($comments)];
                CourseFeedback::firstOrCreate(
                    ['section_id' => $section->id, 'user_id' => $respondent->id],
                    ['rating' => $rating, 'comment' => $comment],
                );
            }
        }
    }

    /**
     * Bookable office-hours slots for the demo faculty over the next two weeks —
     * a mix of open slots and one already booked by the demo student.
     */
    private function seedOfficeHours(User $student, User $smith, ?User $jones): void
    {
        $faculty = array_filter([$smith, $jones]);

        foreach ($faculty as $facultyIndex => $member) {
            for ($week = 0; $week < 2; $week++) {
                for ($i = 0; $i < 3; $i++) {
                    $start = Carbon::now()
                        ->addDays(($week * 7) + 1 + $i)
                        ->setTime(14 + $i, 0);

                    // The first slot of prof. Smith's first week is booked by the
                    // demo student; everything else stays open.
                    $bookedBy = ($facultyIndex === 0 && $week === 0 && $i === 0) ? $student->id : null;

                    OfficeHourSlot::firstOrCreate(
                        ['faculty_id' => $member->id, 'starts_at' => $start],
                        [
                            'ends_at' => $start->copy()->addMinutes(30),
                            'location' => 'Office 3'.($facultyIndex + 1).'0'.($i + 1),
                            'note' => 'Drop-in for coursework questions.',
                            'booked_by' => $bookedBy,
                            'booked_at' => $bookedBy ? now() : null,
                        ],
                    );
                }
            }
        }
    }

    /**
     * Attach camera-proctoring snapshot evidence to the demo student's submitted
     * class-test attempts, writing a placeholder frame per snapshot so the faculty
     * photo-strip actually renders.
     */
    private function seedProctoringSnapshots(User $student): void
    {
        $attempts = ClassTestAttempt::where('user_id', $student->id)
            ->where('status', 'submitted')
            ->orderByDesc('id')
            ->limit(2)
            ->get();

        if ($attempts->isEmpty()) {
            $attempt = $this->createDemoProctoredAttempt($student);
            if ($attempt === null) {
                $this->command->warn('   (snapshots) No class-test attempt available for the demo student; skipped.');

                return;
            }
            $attempts = collect([$attempt]);
        }

        $disk = (string) config('exam_security.snapshots.disk', 'local');
        $storage = Storage::disk($disk);

        // A realistic capture timeline: periodic samples plus a couple of flags.
        $timeline = [
            'periodic', 'periodic', 'face_lost', 'periodic', 'phone_detected', 'periodic', 'multiple_faces',
        ];

        foreach ($attempts as $attempt) {
            if ($attempt->snapshots()->exists()) {
                continue;
            }

            $directory = trim((string) config('exam_security.snapshots.directory', 'exam-snapshots'), '/')."/{$attempt->id}";

            foreach ($timeline as $sequence => $trigger) {
                $bytes = $this->placeholderFrame(strtoupper($trigger).' #'.($sequence + 1));
                $path = $directory.'/frame-'.($sequence + 1).'.jpg';
                $storage->put($path, $bytes);

                ClassTestSnapshot::create([
                    'attempt_id' => $attempt->id,
                    'trigger' => $trigger,
                    'sequence' => $sequence,
                    'disk' => $disk,
                    'path' => $path,
                    'size_bytes' => strlen($bytes),
                ]);
            }

            // Reflect the flagged frames on the attempt's own violation tally.
            $flags = count(array_filter($timeline, fn ($t) => $t !== 'periodic'));
            $attempt->update([
                'violation_count' => max((int) $attempt->violation_count, $flags),
                'risk_score' => max((int) ($attempt->risk_score ?? 0), 45),
            ]);
        }
    }

    /**
     * Course prerequisites (met by the demo student's completed history) and a
     * short section waitlist, so the catalog and admin waitlist views have data.
     */
    private function seedPrerequisitesAndWaitlist(User $student): void
    {
        // CS340 (Software Engineering) requires CS301 — which the demo student is
        // currently taking, not yet completed — so its registration page shows an
        // UNMET prerequisite badge. CS340 is the deliberately-unassigned course, so
        // gating it never affects the self-registration flow the tests exercise.
        $cs340 = Course::where('code', 'CS340')->first();
        $cs301 = Course::where('code', 'CS301')->first();
        if ($cs340 && $cs301 && $cs340->id !== $cs301->id) {
            DB::table('course_prerequisites')->updateOrInsert(
                ['course_id' => $cs340->id, 'prerequisite_id' => $cs301->id],
                ['updated_at' => now(), 'created_at' => now()],
            );
        }

        // Queue two cohort students on a CS340 Section A waitlist (FIFO), keeping
        // the demo student's own enrolled sections untouched.
        if ($cs340) {
            $section = $cs340->sections()->where('label', 'A')
                ->whereHas('term', fn ($q) => $q->where('is_current', true))
                ->first();
            if ($section) {
                foreach ($this->classmatesFor($cs340, $student->id, 2)->values() as $waitlisted) {
                    DB::table('section_waitlists')->updateOrInsert(
                        ['section_id' => $section->id, 'user_id' => $waitlisted->id],
                        ['updated_at' => now(), 'created_at' => now()],
                    );
                }
            }
        }
    }

    /**
     * Enrolled classmates in a course's current Section A, excluding the demo
     * student.
     *
     * @return Collection<int, User>
     */
    private function classmatesFor(Course $course, int $excludeUserId, int $limit): Collection
    {
        $section = $course->sections()
            ->where('label', 'A')
            ->whereHas('term', fn ($q) => $q->where('is_current', true))
            ->first();

        if (! $section) {
            return collect();
        }

        return $section->students()
            ->wherePivot('status', 'enrolled')
            ->where('users.id', '!=', $excludeUserId)
            ->limit($limit)
            ->get();
    }

    /**
     * Fallback: create one submitted, proctored class-test attempt for the demo
     * student when the bulk seeder happened to leave them without one.
     */
    private function createDemoProctoredAttempt(User $student): ?ClassTestAttempt
    {
        $sectionIds = $student->enrolledSectionIds();
        if ($sectionIds->isEmpty()) {
            return null;
        }

        $test = ClassTest::where('status', 'published')
            ->whereIn('section_id', $sectionIds)
            ->first();

        if (! $test) {
            return null;
        }

        $totalMarks = (int) $test->total_marks ?: (int) $test->questions()->sum('marks') ?: 20;

        return ClassTestAttempt::firstOrCreate(
            ['class_test_id' => $test->id, 'user_id' => $student->id],
            [
                'status' => 'submitted',
                'started_at' => now()->subDays(2),
                'submitted_at' => now()->subDays(2)->addMinutes(20),
                'score' => (int) round($totalMarks * 0.8),
                'total_marks' => $totalMarks,
                'violation_count' => 0,
                'risk_score' => 20,
            ],
        );
    }

    /**
     * Build a small JPEG placeholder frame (GD when available, else a 1×1 grey
     * fallback), so seeded snapshots resolve to a real image on the private disk.
     */
    private function placeholderFrame(string $label): string
    {
        if (function_exists('imagecreatetruecolor')) {
            $img = imagecreatetruecolor(320, 240);
            $bg = imagecolorallocate($img, 30, 32, 44);
            imagefilledrectangle($img, 0, 0, 320, 240, $bg);
            $fg = imagecolorallocate($img, 205, 205, 215);
            imagestring($img, 5, 90, 100, 'DEMO FRAME', $fg);
            imagestring($img, 3, 70, 130, $label, $fg);
            ob_start();
            imagejpeg($img, null, 70);
            $bytes = (string) ob_get_clean();
            imagedestroy($img);

            return $bytes;
        }

        return (string) base64_decode(self::FALLBACK_JPEG, true);
    }
}
