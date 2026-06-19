<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Term;
use Illuminate\Database\Seeder;

/**
 * Authoritative source for academic terms: a completed prior term, the single
 * current term (registration open), and an upcoming term. Runs before the demo
 * AcademicSeeder, whose firstOrCreate(slug) calls then resolve to these rows.
 *
 * Invariant: exactly one term carries is_current = true.
 */
class TermSeeder extends Seeder
{
    public function run(): void
    {
        $terms = [
            [
                'slug' => 'spring-2026',
                'name' => 'Spring 2026',
                'start_date' => '2026-01-15',
                'end_date' => '2026-05-15',
                'is_current' => false,
                'is_registration_open' => false,
            ],
            [
                'slug' => 'summer-2026',
                'name' => 'Summer 2026',
                'start_date' => '2026-06-01',
                'end_date' => '2026-08-31',
                'is_current' => true,
                'is_registration_open' => true,
            ],
            [
                'slug' => 'fall-2026',
                'name' => 'Fall 2026',
                'start_date' => '2026-09-15',
                'end_date' => '2026-12-31',
                'is_current' => false,
                'is_registration_open' => false,
            ],
        ];

        foreach ($terms as $term) {
            Term::firstOrCreate(['slug' => $term['slug']], $term);
        }

        // Enforce the single-current-term invariant defensively.
        $current = Term::where('slug', 'summer-2026')->first();
        if ($current) {
            Term::where('id', '!=', $current->id)->where('is_current', true)
                ->update(['is_current' => false]);
            $current->update(['is_current' => true]);
        }

        $this->command->info('   ✓ Terms seeded (3, current = Summer 2026)');
    }
}
