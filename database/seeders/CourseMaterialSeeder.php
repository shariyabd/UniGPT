<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Section;
use App\Models\Term;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Seeds published course materials for every catalog section in the current term.
 * Each material carries both course_id and section_id (section isolation), and is
 * attributed to the section's teaching faculty. Demo sections are left untouched.
 */
class CourseMaterialSeeder extends Seeder
{
    public function run(): void
    {
        $term = Term::currentTerm();
        if (! $term) {
            $this->command->warn('   No current term; run TermSeeder first.');

            return;
        }

        $perSection = (int) config('seeder.materials_per_section', 3);
        $demoCodes = (array) config('seeder.demo_course_codes', []);
        $now = Carbon::now();

        $template = [
            ['title' => 'Week 1 — Introduction & Course Outline', 'type' => 'lecture', 'week' => 1],
            ['title' => 'Week 2 — Core Concepts (Slides)', 'type' => 'slides', 'week' => 2],
            ['title' => 'Recommended Reading List', 'type' => 'reading', 'week' => 2],
            ['title' => 'Week 3 — Worked Examples', 'type' => 'lecture', 'week' => 3],
            ['title' => 'Supplementary Video Lecture', 'type' => 'video', 'week' => 4],
        ];

        $rows = [];

        Section::query()
            ->where('term_id', $term->id)
            ->whereHas('course', fn ($q) => $q->whereNotIn('code', $demoCodes))
            ->select(['id', 'course_id', 'faculty_id'])
            ->chunkById(200, function ($sections) use (&$rows, $template, $perSection, $now) {
                foreach ($sections as $section) {
                    foreach (array_slice($template, 0, $perSection) as $material) {
                        $rows[] = [
                            'course_id' => $section->course_id,
                            'section_id' => $section->id,
                            'title' => $material['title'],
                            'description' => $material['title'].' for this section.',
                            'type' => $material['type'],
                            'week' => $material['week'],
                            'is_published' => true,
                            'downloads' => random_int(0, 120),
                            'uploaded_by' => $section->faculty_id,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }
                }
            });

        foreach (array_chunk($rows, 1000) as $chunk) {
            DB::table('course_materials')->insert($chunk);
        }

        $this->command->info('   ✓ Course materials seeded ('.count($rows).')');
    }
}
