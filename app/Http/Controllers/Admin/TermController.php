<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domain\Academic\Services\TermService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TermRequest;
use App\Models\Term;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class TermController extends Controller
{
    public function __construct(
        private readonly TermService $terms,
        private readonly ActivityLogger $activity,
    ) {}

    public function index(): Response
    {
        $terms = $this->terms->overview();

        // Only the standard names not yet used are offered, and "Add" is hidden
        // once all three exist.
        $availableNames = array_values(array_diff(Term::STANDARD_NAMES, Term::pluck('name')->all()));

        return Inertia::render('Admin/Terms', [
            'terms' => $terms,
            'availableNames' => $availableNames,
            'canAddTerm' => $terms->count() < count(Term::STANDARD_NAMES) && $availableNames !== [],
        ]);
    }

    public function store(TermRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['slug'] = Str::slug($data['name']);

        $term = Term::create($data);

        $this->activity->log('term.created', "Created term {$term->name}", $term, [], $request->user());

        return back()->with('success', 'Term created.');
    }

    public function setCurrent(Term $term): RedirectResponse
    {
        $this->terms->setCurrent($term);

        return back()->with('success', "{$term->name} is now the current term.");
    }

    public function toggleRegistration(Term $term): RedirectResponse
    {
        $term->update(['is_registration_open' => ! $term->is_registration_open]);

        return back()->with('success', $term->is_registration_open
            ? "Registration opened for {$term->name}."
            : "Registration closed for {$term->name}.");
    }

    public function close(Term $term): RedirectResponse
    {
        $validated = request()->validate([
            'next_term_id' => ['nullable', Rule::exists('terms', 'id')->where(fn ($q) => $q->whereNot('id', $term->id))],
        ]);

        $next = isset($validated['next_term_id']) ? Term::find($validated['next_term_id']) : null;

        $result = $this->terms->close($term, $next);

        $this->activity->log(
            'term.closed',
            "Closed term {$term->name}",
            $term,
            $result,
            request()->user(),
        );

        return back()->with('success', "Closed {$term->name}: {$result['enrollments']} enrollment(s) completed, {$result['sections']} section(s) archived.");
    }

    public function destroy(Term $term): RedirectResponse
    {
        if ($term->is_current) {
            return back()->with('error', 'Cannot delete the current term.');
        }

        if ($term->sections()->exists()) {
            return back()->with('error', 'Cannot delete a term that still has sections.');
        }

        $term->delete();

        return back()->with('success', 'Term deleted.');
    }
}
