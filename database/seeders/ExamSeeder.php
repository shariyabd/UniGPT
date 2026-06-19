<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Section;
use App\Models\Term;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Seeds the standard exam structure (class test, midterm, final) for every
 * catalog section in the current term. Section-scoped (course_id + section_id),
 * attributed to the section's faculty. No quiz submissions or marks are seeded —
 * those are user-driven.
 */
class ExamSeeder extends Seeder
{
    public function run(): void
    {
        $term = Term::currentTerm();
        if (! $term) {
            $this->command->warn('   No current term; run TermSeeder first.');

            return;
        }

        $perSection = (int) config('seeder.exams_per_section', 3);
        $demoCodes = (array) config('seeder.demo_course_codes', []);
        $now = Carbon::now();

        // [title, type, day offset, start_time, duration, total_marks]
        $template = [
            ['Class Test 1', 'quiz', -14, '09:00', 30, 20],
            ['Midterm Examination', 'midterm', 12, '10:00', 90, 50],
            ['Final Examination', 'final', 45, '09:00', 180, 100],
            ['Practical Assessment', 'practical', 30, '11:00', 120, 40],
        ];

        $rows = [];

        Section::query()
            ->where('term_id', $term->id)
            ->whereHas('course', fn ($q) => $q->whereNotIn('code', $demoCodes))
            ->select(['id', 'course_id', 'faculty_id'])
            ->chunkById(200, function ($sections) use (&$rows, $template, $perSection, $now) {
                foreach ($sections as $section) {
                    foreach (array_slice($template, 0, $perSection) as $i => [$title, $type, $dayOffset, $time, $duration, $marks]) {
                        $rows[] = [
                            'course_id' => $section->course_id,
                            'section_id' => $section->id,
                            'title' => $title,
                            'type' => $type,
                            'exam_date' => $now->copy()->addDays($dayOffset)->toDateString(),
                            'start_time' => $time,
                            'duration_minutes' => $duration,
                            'location' => 'Hall '.chr(65 + ($i % 4)),
                            'total_marks' => $marks,
                            'instructions' => 'Bring your student ID. No electronic devices permitted.',
                            'created_by' => $section->faculty_id,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }
                }
            });

        foreach (array_chunk($rows, 1000) as $chunk) {
            DB::table('exams')->insert($chunk);
        }

        $this->command->info('   ✓ Exams seeded ('.count($rows).')');
    }
}
