<?php

declare(strict_types=1);

namespace App\Http\Controllers\Student;

use App\Domain\Analytics\Services\LeaderboardService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Student\LeaderboardSettingsRequest;
use App\Models\Section;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Opt-in gamified leaderboard — department, semester and section scopes.
 */
class LeaderboardController extends Controller
{
    private const SCOPES = ['department', 'semester', 'section'];

    public function __construct(private readonly LeaderboardService $leaderboard) {}

    public function index(Request $request): Response
    {
        $user = $request->user();

        $scope = in_array($request->query('scope'), self::SCOPES, true)
            ? (string) $request->query('scope')
            : 'department';

        $sections = $user->enrolledSections()
            ->wherePivotNotIn('status', ['pending'])
            ->with('course:id,code')
            ->get()
            ->map(fn (Section $section) => [
                'id' => $section->id,
                'label' => trim(($section->course?->code ?? 'Section').' · '.$section->label),
            ])
            ->values();

        $sectionId = $request->integer('section_id') ?: ($sections->first()['id'] ?? null);

        $rankings = ($scope === 'section' && $sectionId === null)
            ? ['entries' => [], 'viewerRank' => null, 'totalRanked' => 0]
            : $this->leaderboard->rankings($user, $scope, $sectionId);

        return Inertia::render('Student/Leaderboard', [
            'scope' => $scope,
            'sectionId' => $sectionId,
            'sections' => $sections,
            'rankings' => $rankings,
            'settings' => [
                'optIn' => (bool) $user->leaderboard_opt_in,
                'alias' => $user->leaderboard_alias,
            ],
        ]);
    }

    public function updateSettings(LeaderboardSettingsRequest $request): RedirectResponse
    {
        $request->user()->update($request->validated());

        return back()->with('success', 'Leaderboard settings updated.');
    }
}
