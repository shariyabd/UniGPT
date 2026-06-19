<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Section;
use App\Models\Term;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Seeds attendance for current-term bulk enrollments: a handful of recent
 * sessions per student per section, with a realistic present-heavy status mix.
 * Each record links student + course + section + the marking faculty. One row
 * per (course, student, date) — the table's unique key is respected by using a
 * distinct date per session.
 */
class AttendanceSeeder extends Seeder
{
    public function run(): void
    {
        $term = Term::currentTerm();
        if (! $term) {
            $this->command->warn('   No current term; run TermSeeder first.');

            return;
        }

        $sessions = (int) config('seeder.attendance_sessions', 6);
        $demoCodes = (array) config('seeder.demo_course_codes', []);
        $now = Carbon::now();

        // Catalog sections (current term) → marking faculty.
        $sections = Section::query()
            ->where('term_id', $term->id)
            ->whereHas('course', fn ($q) => $q->whereNotIn('code', $demoCodes))
            ->pluck('faculty_id', 'id');

        if ($sections->isEmpty()) {
            $this->command->warn('   No catalog sections; nothing to attend.');

            return;
        }

        // Distinct recent session dates (two-day spacing keeps them unique).
        $dates = [];
        for ($i = 0; $i < $sessions; $i++) {
            $dates[] = $now->copy()->subDays($i * 2)->toDateString();
        }

        $sectionIds = $sections->keys()->all();
        $rows = [];
        $total = 0;

        DB::table('course_user')
            ->whereIn('section_id', $sectionIds)
            ->where('status', 'enrolled')
            ->select(['user_id', 'course_id', 'section_id'])
            ->orderBy('id')
            ->chunk(1000, function ($enrollments) use (&$rows, &$total, $sections, $dates, $now) {
                foreach ($enrollments as $enrollment) {
                    $markedBy = $sections->get($enrollment->section_id);
                    foreach ($dates as $date) {
                        $rows[] = [
                            'course_id' => $enrollment->course_id,
                            'section_id' => $enrollment->section_id,
                            'user_id' => $enrollment->user_id,
                            'date' => $date,
                            'status' => $this->weightedStatus(),
                            'marked_by' => $markedBy,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                        $total++;
                    }

                    if (count($rows) >= 2000) {
                        DB::table('attendance_records')->insert($rows);
                        $rows = [];
                    }
                }
            });

        if ($rows !== []) {
            DB::table('attendance_records')->insert($rows);
        }

        $this->command->info("   ✓ Attendance seeded ({$total} records)");
    }

    private function weightedStatus(): string
    {
        $roll = random_int(1, 100);

        return match (true) {
            $roll <= 75 => 'present',
            $roll <= 87 => 'absent',
            $roll <= 96 => 'late',
            default => 'excused',
        };
    }
}
