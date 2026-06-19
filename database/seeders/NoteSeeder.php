<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Section;
use App\Models\Term;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Seeds personal study notes for bulk students, each tied to one of the courses
 * the student is enrolled in this term.
 */
class NoteSeeder extends Seeder
{
    private const TEMPLATES = [
        ['Lecture Key Takeaways', 'Summary of the main concepts and formulas from this week to revise before the exam.'],
        ['Questions for Office Hours', 'Points to clarify with the instructor: derivations, edge cases, and assignment scope.'],
        ['Revision Checklist', 'Topics to revise — definitions, worked examples, and past-paper practice problems.'],
        ['Lab Observations', 'Setup, procedure and results recorded during the latest lab session.'],
    ];

    public function run(): void
    {
        $term = Term::currentTerm();
        if (! $term) {
            return;
        }

        $perStudent = (int) config('seeder.notes_per_student', 2);
        $demoCodes = (array) config('seeder.demo_course_codes', []);
        $now = Carbon::now();

        $sectionIds = Section::query()
            ->where('term_id', $term->id)
            ->whereHas('course', fn ($q) => $q->whereNotIn('code', $demoCodes))
            ->pluck('id')->all();

        $coursesByStudent = DB::table('course_user')
            ->whereIn('section_id', $sectionIds)
            ->where('status', 'enrolled')
            ->get(['user_id', 'course_id'])
            ->groupBy('user_id');

        $rows = [];
        foreach ($coursesByStudent as $userId => $enrollments) {
            $courseIds = $enrollments->pluck('course_id')->all();

            for ($n = 0; $n < $perStudent; $n++) {
                [$title, $content] = self::TEMPLATES[$n % count(self::TEMPLATES)];

                $rows[] = [
                    'user_id' => $userId,
                    'course_id' => $courseIds[array_rand($courseIds)],
                    'title' => $title,
                    'content' => $content,
                    'is_pinned' => random_int(1, 5) === 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        foreach (array_chunk($rows, 1000) as $chunk) {
            DB::table('notes')->insert($chunk);
        }

        $this->command->info('   ✓ Notes seeded ('.count($rows).')');
    }
}
