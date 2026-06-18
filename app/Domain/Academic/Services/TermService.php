<?php

declare(strict_types=1);

namespace App\Domain\Academic\Services;

use App\Models\Section;
use App\Models\Term;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Academic-term management: listing, designating the current term, and the
 * manual end-of-term rollover (close).
 */
class TermService
{
    /**
     * All terms with offering/enrollment counts, current term first.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function overview(): Collection
    {
        return Term::orderByDesc('is_current')
            ->orderByDesc('start_date')
            ->get()
            ->map(fn (Term $term) => [
                'id' => $term->id,
                'name' => $term->name,
                'slug' => $term->slug,
                'startDate' => $term->start_date?->toDateString(),
                'endDate' => $term->end_date?->toDateString(),
                'isCurrent' => $term->is_current,
                'isRegistrationOpen' => $term->is_registration_open,
                'sections' => Section::where('term_id', $term->id)->count(),
                'activeEnrollments' => DB::table('course_user')
                    ->where('term_id', $term->id)
                    ->where('status', 'enrolled')
                    ->count(),
            ]);
    }

    /**
     * Make a term the single current term.
     */
    public function setCurrent(Term $term): void
    {
        DB::transaction(function () use ($term) {
            Term::where('is_current', true)->whereKeyNot($term->id)->update(['is_current' => false]);
            $term->update(['is_current' => true]);
        });
    }

    /**
     * Close (roll over) a term: mark its sections historical, complete its
     * still-active enrolments (grades are left untouched/frozen), and clear its
     * current flag. Optionally promote the next term to current.
     *
     * @return array{sections: int, enrollments: int}
     */
    public function close(Term $term, ?Term $next = null): array
    {
        return DB::transaction(function () use ($term, $next) {
            $sections = Section::where('term_id', $term->id)->update(['is_active' => false]);

            $enrollments = DB::table('course_user')
                ->where('term_id', $term->id)
                ->where('status', 'enrolled')
                ->update(['status' => 'completed']);

            $term->update(['is_current' => false]);

            if ($next !== null) {
                $this->setCurrent($next);
            }

            return ['sections' => $sections, 'enrollments' => $enrollments];
        });
    }
}
