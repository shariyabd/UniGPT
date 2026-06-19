<?php

namespace Database\Seeders;

use App\Domain\User\Models\User;
use App\Models\Assignment;
use App\Models\Course;
use App\Models\CourseMaterial;
use App\Models\Exam;
use App\Models\Note;
use App\Models\Section;
use App\Models\Task;
use App\Models\Term;
use Illuminate\Database\Seeder;

class AcademicSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding academic data...');

        $faculty = User::where('email', 'prof.smith@university.edu')->first();
        $student = User::where('email', 'student@university.edu')->first();

        if (! $faculty || ! $student) {
            $this->command->warn('   Demo faculty/student missing; run RBACSeeder first.');

            return;
        }

        $departmentId = $faculty->department_id ?? $student->department_id;

        [$priorTerm, $currentTerm] = $this->seedTerms();

        $blueprint = [
            ['code' => 'CS301', 'name' => 'Data Structures & Algorithms', 'semester' => 5, 'credits' => 4],
            ['code' => 'CS305', 'name' => 'Machine Learning Fundamentals', 'semester' => 5, 'credits' => 3],
            ['code' => 'CS310', 'name' => 'Database Systems', 'semester' => 5, 'credits' => 3],
            ['code' => 'CS320', 'name' => 'Operating Systems', 'semester' => 6, 'credits' => 4],
            // Semester-5 offerings the demo student is NOT auto-enrolled in, so the
            // self-registration page has open courses to choose from.
            ['code' => 'CS330', 'name' => 'Computer Networks', 'semester' => 5, 'credits' => 3],
            ['code' => 'CS340', 'name' => 'Software Engineering', 'semester' => 5, 'credits' => 3],
        ];

        foreach ($blueprint as $i => $data) {
            $course = Course::firstOrCreate(
                ['code' => $data['code']],
                [
                    'name' => $data['name'],
                    'description' => "An in-depth course on {$data['name']}.",
                    'department_id' => $departmentId,
                    'faculty_id' => $faculty->id,
                    'semester' => $data['semester'],
                    'credits' => $data['credits'],
                    'schedule' => [
                        'lectures' => 'Mon/Wed 10:00–11:30',
                        'classroom' => 'Room '.(101 + $i),
                        'office_hours' => 'Tue 14:00–16:00',
                    ],
                    'max_enrollment' => 60,
                ],
            );

            // Every course is taught through a section (offering); child records
            // attach to it. Create it before seeding materials/assignments/exams.
            $section = $this->sectionFor($course, $currentTerm->id, $faculty->id);

            // Enroll the demo student in the first three courses.
            if ($i < 3) {
                $course->students()->syncWithoutDetaching([
                    $student->id => [
                        'role' => 'student',
                        'status' => 'enrolled',
                        'grade' => ['A', 'B+', 'A-'][$i] ?? null,
                        'progress' => [72, 58, 65][$i] ?? 50,
                        'term_id' => $currentTerm->id,
                        'section_id' => $section->id,
                        'enrolled_at' => now(),
                    ],
                ]);
            }

            // Demonstrate the assigned→register flow: the registrar has placed the
            // demo student into CS330 Section A as a pending placement, so it shows
            // up on the registration page awaiting their confirmation. CS340 is
            // left unassigned to illustrate a course the student cannot register for.
            if ($data['code'] === 'CS330') {
                $course->students()->syncWithoutDetaching([
                    $student->id => [
                        'role' => 'student',
                        'status' => 'pending',
                        'term_id' => $currentTerm->id,
                        'section_id' => $section->id,
                        'enrolled_at' => null,
                    ],
                ]);
            }

            $this->seedMaterials($course, $faculty->id);
            $this->seedAssignments($course, $faculty->id, $student->id);
            $this->seedExams($course, $faculty->id);

            if ($i < 3) {
                $this->seedAttendance($course, $faculty->id, $student->id);
            }
        }

        // Demonstrate multi-section + faculty assignment: a second section (B) of
        // CS301 this term, taught by a different faculty member.
        $jones = User::where('email', 'prof.jones@university.edu')->first();
        if (($cs301 = Course::where('code', 'CS301')->first()) && $jones) {
            $this->sectionFor($cs301, $currentTerm->id, $jones->id, 'B');
        }

        // A completed prior term so current/past separation, the transcript and
        // CGPA history have real data to show.
        $this->seedHistory($student, $faculty->id, $departmentId, $priorTerm->id);

        // Personal notes and to-do tasks so the demo student's Productivity and
        // Calendar pages are populated (bulk seeders skip the demo accounts).
        $this->seedDemoProductivity($student);

        $this->command->info('   ✓ Academic data seeded ('.count($blueprint).' courses)');
    }

    /**
     * Seed study notes and academic to-do tasks for the demo student, tied to
     * their currently-enrolled courses.
     */
    private function seedDemoProductivity(User $student): void
    {
        $courses = $student->enrolledCourses()
            ->wherePivot('status', 'enrolled')
            ->get(['courses.id', 'code']);

        if ($courses->isEmpty()) {
            return;
        }

        $notes = [
            ['title' => 'Lecture Key Takeaways', 'content' => 'Summary of the main concepts and formulas from this week to revise before the exam.', 'is_pinned' => true],
            ['title' => 'Questions for Office Hours', 'content' => 'Points to clarify with the instructor: derivations, edge cases, and assignment scope.', 'is_pinned' => false],
            ['title' => 'Revision Checklist', 'content' => 'Topics to revise — definitions, worked examples, and past-paper practice problems.', 'is_pinned' => false],
        ];

        foreach ($notes as $i => $note) {
            Note::firstOrCreate(
                ['user_id' => $student->id, 'title' => $note['title']],
                [
                    'course_id' => $courses[$i % $courses->count()]->id,
                    'content' => $note['content'],
                    'is_pinned' => $note['is_pinned'],
                ],
            );
        }

        $tasks = [
            ['title' => 'Submit Lab Report', 'priority' => 'high', 'days' => 3, 'done' => false],
            ['title' => 'Prepare Class Presentation', 'priority' => 'medium', 'days' => 7, 'done' => false],
            ['title' => 'Complete Reading Assignment', 'priority' => 'low', 'days' => -2, 'done' => true],
            ['title' => 'Group Project Meeting', 'priority' => 'medium', 'days' => 5, 'done' => false],
        ];

        foreach ($tasks as $i => $task) {
            Task::firstOrCreate(
                ['user_id' => $student->id, 'title' => $task['title']],
                [
                    'course_id' => $courses[$i % $courses->count()]->id,
                    'description' => $task['title'].' — coursework item to track this semester.',
                    'due_date' => now()->addDays($task['days'])->toDateString(),
                    'priority' => $task['priority'],
                    'is_completed' => $task['done'],
                    'completed_at' => $task['done'] ? now()->subDay() : null,
                ],
            );
        }
    }

    /**
     * Create the prior and current academic terms.
     *
     * @return array{0: Term, 1: Term}
     */
    private function seedTerms(): array
    {
        $prior = Term::firstOrCreate(
            ['slug' => 'spring-2026'],
            ['name' => 'Spring 2026', 'start_date' => '2026-01-15', 'end_date' => '2026-05-15', 'is_current' => false],
        );

        $current = Term::firstOrCreate(
            ['slug' => 'summer-2026'],
            ['name' => 'Summer 2026', 'start_date' => '2026-06-01', 'end_date' => '2026-08-31', 'is_current' => true, 'is_registration_open' => true],
        );

        return [$prior, $current];
    }

    /**
     * Find or create a section (offering) for a course in a given term.
     */
    private function sectionFor(Course $course, ?int $termId, ?int $facultyId, string $label = 'A'): Section
    {
        return Section::firstOrCreate(
            ['course_id' => $course->id, 'term_id' => $termId, 'label' => $label],
            [
                'faculty_id' => $facultyId,
                'schedule' => $course->schedule,
                'max_enrollment' => $course->max_enrollment ?? 60,
                'is_active' => $course->is_active ?? true,
            ],
        );
    }

    /**
     * Seed a completed earlier semester (prior term) for the demo student so the
     * portal shows a real academic history with graded, retained courses.
     */
    private function seedHistory(User $student, int $facultyId, ?int $departmentId, int $priorTermId): void
    {
        $history = [
            ['code' => 'CS201', 'name' => 'Discrete Mathematics', 'credits' => 3, 'grade' => 'A-'],
            ['code' => 'CS210', 'name' => 'Computer Architecture', 'credits' => 3, 'grade' => 'B+'],
            ['code' => 'CS220', 'name' => 'Object-Oriented Programming', 'credits' => 4, 'grade' => 'A'],
        ];

        foreach ($history as $i => $data) {
            $course = Course::firstOrCreate(
                ['code' => $data['code']],
                [
                    'name' => $data['name'],
                    'description' => "An in-depth course on {$data['name']}.",
                    'department_id' => $departmentId,
                    'faculty_id' => $facultyId,
                    'semester' => 4,
                    'credits' => $data['credits'],
                    'schedule' => [
                        'lectures' => 'Tue/Thu 09:00–10:30',
                        'classroom' => 'Room '.(201 + $i),
                        'office_hours' => 'Wed 14:00–16:00',
                    ],
                    'max_enrollment' => 60,
                    'is_active' => false,
                ],
            );

            $section = $this->sectionFor($course, $priorTermId, $facultyId);

            $course->students()->syncWithoutDetaching([
                $student->id => [
                    'role' => 'student',
                    'status' => 'completed',
                    'grade' => $data['grade'],
                    'progress' => 100,
                    'term_id' => $priorTermId,
                    'section_id' => $section->id,
                    'enrolled_at' => now()->subMonths(5),
                ],
            ]);

            // Past materials remain available to the student (read-only retention).
            $this->seedMaterials($course, $facultyId);
        }
    }

    private function seedAttendance(Course $course, int $facultyId, int $studentId): void
    {
        // Mark the last 10 weekday sessions; mostly present, with a couple of misses.
        $absentOn = [3, 7]; // indices that are absent/late for a realistic rate
        for ($i = 0; $i < 10; $i++) {
            $status = match (true) {
                $i === $absentOn[0] => 'absent',
                $i === $absentOn[1] => 'late',
                default => 'present',
            };

            $course->attendanceRecords()->firstOrCreate(
                ['user_id' => $studentId, 'date' => now()->subDays($i * 2)->toDateString()],
                ['status' => $status, 'marked_by' => $facultyId],
            );
        }
    }

    private function seedExams(Course $course, int $facultyId): void
    {
        $exams = [
            ['title' => 'Quiz 1', 'type' => 'quiz', 'days' => -14, 'time' => '09:00', 'duration' => 30, 'marks' => 20],
            ['title' => 'Midterm Examination', 'type' => 'midterm', 'days' => 10, 'time' => '10:00', 'duration' => 90, 'marks' => 50],
            ['title' => 'Final Examination', 'type' => 'final', 'days' => 45, 'time' => '09:00', 'duration' => 180, 'marks' => 100],
        ];

        foreach ($exams as $i => $data) {
            Exam::firstOrCreate(
                ['course_id' => $course->id, 'title' => $data['title']],
                [
                    'type' => $data['type'],
                    'exam_date' => now()->addDays($data['days'])->toDateString(),
                    'start_time' => $data['time'],
                    'duration_minutes' => $data['duration'],
                    'location' => 'Hall '.chr(65 + ($i % 3)),
                    'total_marks' => $data['marks'],
                    'instructions' => 'Bring your student ID. No electronic devices permitted.',
                    'created_by' => $facultyId,
                ],
            );
        }
    }

    private function seedMaterials(Course $course, int $facultyId): void
    {
        $materials = [
            ['title' => 'Week 1 — Introduction & Syllabus', 'type' => 'lecture', 'week' => 1],
            ['title' => 'Week 2 — Core Concepts', 'type' => 'slides', 'week' => 2],
            ['title' => 'Recommended Reading List', 'type' => 'reading', 'week' => 2],
        ];

        foreach ($materials as $material) {
            CourseMaterial::firstOrCreate(
                ['course_id' => $course->id, 'title' => $material['title']],
                [
                    'description' => $material['title'].' for '.$course->code,
                    'type' => $material['type'],
                    'week' => $material['week'],
                    'is_published' => true,
                    'uploaded_by' => $facultyId,
                ],
            );
        }
    }

    private function seedAssignments(Course $course, int $facultyId, int $studentId): void
    {
        $assignments = [
            ['title' => 'Assignment 1 — Problem Set', 'type' => 'homework', 'points' => 100, 'days' => 7],
            ['title' => 'Midterm Project', 'type' => 'project', 'points' => 150, 'days' => 21],
        ];

        foreach ($assignments as $data) {
            $assignment = Assignment::firstOrCreate(
                ['course_id' => $course->id, 'title' => $data['title']],
                [
                    'description' => $data['title'].' for '.$course->name,
                    'type' => $data['type'],
                    'total_points' => $data['points'],
                    'due_at' => now()->addDays($data['days']),
                    'rubric' => [
                        ['criterion' => 'Correctness', 'points' => (int) ($data['points'] * 0.5)],
                        ['criterion' => 'Clarity', 'points' => (int) ($data['points'] * 0.3)],
                        ['criterion' => 'Presentation', 'points' => (int) ($data['points'] * 0.2)],
                    ],
                    'status' => 'published',
                    'created_by' => $facultyId,
                ],
            );

            // One submitted (ungraded) submission from the demo student on the first assignment.
            if (str_contains($data['title'], 'Assignment 1')) {
                $assignment->submissions()->firstOrCreate(
                    ['user_id' => $studentId],
                    [
                        'content' => 'My submission for '.$assignment->title,
                        'status' => 'submitted',
                        'submitted_at' => now()->subDay(),
                    ],
                );
            }
        }
    }
}
