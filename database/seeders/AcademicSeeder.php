<?php

namespace Database\Seeders;

use App\Domain\User\Models\User;
use App\Models\Assignment;
use App\Models\Course;
use App\Models\CourseMaterial;
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

        $blueprint = [
            ['code' => 'CS301', 'name' => 'Data Structures & Algorithms', 'semester' => 5, 'credits' => 4],
            ['code' => 'CS305', 'name' => 'Machine Learning Fundamentals', 'semester' => 5, 'credits' => 3],
            ['code' => 'CS310', 'name' => 'Database Systems', 'semester' => 5, 'credits' => 3],
            ['code' => 'CS320', 'name' => 'Operating Systems', 'semester' => 6, 'credits' => 4],
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

            // Enroll the demo student in the first three courses.
            if ($i < 3) {
                $course->students()->syncWithoutDetaching([
                    $student->id => [
                        'role' => 'student',
                        'status' => 'enrolled',
                        'grade' => ['A', 'B+', 'A-'][$i] ?? null,
                        'progress' => [72, 58, 65][$i] ?? 50,
                        'enrolled_at' => now(),
                    ],
                ]);
            }

            $this->seedMaterials($course, $faculty->id);
            $this->seedAssignments($course, $faculty->id, $student->id);
        }

        $this->command->info('   ✓ Academic data seeded ('.count($blueprint).' courses)');
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
