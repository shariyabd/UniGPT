<?php

declare(strict_types=1);

namespace App\Http\Controllers\Community;

use App\Domain\Community\Services\DiscussionService;
use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\PostComment;
use App\Models\PostReport;
use App\Models\Section;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Course/section discussion feed — shared by students and faculty. Access is
 * relationship-based (enrolment / teaching), so both roles use these routes.
 */
class DiscussionController extends Controller
{
    public function __construct(private readonly DiscussionService $discussions) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        $sections = $this->discussions->accessibleSections($user);

        $activeId = $request->integer('section_id') ?: ($sections->first()['id'] ?? null);
        $activeSection = $activeId ? Section::find($activeId) : null;

        if ($activeSection !== null) {
            abort_unless($this->discussions->canAccess($user, $activeSection), 403);
        }

        return Inertia::render('Community/Discussions', [
            'sections' => $sections,
            'activeSectionId' => $activeSection?->id,
            'canModerate' => $activeSection ? $this->discussions->canModerate($user, $activeSection) : false,
            'posts' => $activeSection
                ? $this->discussions->feed($activeSection, $user)
                : ['data' => []],
        ]);
    }

    public function storePost(Request $request, Section $section): RedirectResponse
    {
        $this->authorizeAccess($request, $section);

        $data = $request->validate(['body' => ['required', 'string', 'max:5000']]);
        $this->discussions->createPost($section, $request->user(), $data['body']);

        return back()->with('success', 'Posted.');
    }

    public function togglePin(Request $request, Post $post): RedirectResponse
    {
        abort_unless($this->discussions->canModerate($request->user(), $post->section), 403);

        $post->update(['is_pinned' => ! $post->is_pinned]);

        return back();
    }

    public function destroyPost(Request $request, Post $post): RedirectResponse
    {
        $canModerate = $this->discussions->canModerate($request->user(), $post->section);
        abort_unless($canModerate || $post->user_id === $request->user()->id, 403);

        $post->delete();

        return back()->with('success', 'Post removed.');
    }

    public function toggleLike(Request $request, Post $post): RedirectResponse
    {
        $this->authorizeAccess($request, $post->section);

        $this->discussions->toggleLike($post, $request->user());

        return back();
    }

    public function storeComment(Request $request, Post $post): RedirectResponse
    {
        $this->authorizeAccess($request, $post->section);

        $data = $request->validate(['body' => ['required', 'string', 'max:2000']]);
        $this->discussions->createComment($post, $request->user(), $data['body']);

        return back()->with('success', 'Comment added.');
    }

    public function destroyComment(Request $request, PostComment $comment): RedirectResponse
    {
        $canModerate = $this->discussions->canModerate($request->user(), $comment->post->section);
        abort_unless($canModerate || $comment->user_id === $request->user()->id, 403);

        $comment->delete();

        return back()->with('success', 'Comment removed.');
    }

    public function reportPost(Request $request, Post $post): RedirectResponse
    {
        $this->authorizeAccess($request, $post->section);

        $data = $request->validate(['reason' => ['required', 'string', 'max:300']]);
        PostReport::create([
            'post_id' => $post->id,
            'reporter_id' => $request->user()->id,
            'reason' => $data['reason'],
        ]);

        return back()->with('success', 'Report submitted for review.');
    }

    public function reportComment(Request $request, PostComment $comment): RedirectResponse
    {
        $this->authorizeAccess($request, $comment->post->section);

        $data = $request->validate(['reason' => ['required', 'string', 'max:300']]);
        PostReport::create([
            'post_comment_id' => $comment->id,
            'reporter_id' => $request->user()->id,
            'reason' => $data['reason'],
        ]);

        return back()->with('success', 'Report submitted for review.');
    }

    private function authorizeAccess(Request $request, Section $section): void
    {
        abort_unless($this->discussions->canAccess($request->user(), $section), 403);
    }
}
