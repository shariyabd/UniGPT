<?php

declare(strict_types=1);

namespace App\Domain\Community\Services;

use App\Domain\User\Models\User;
use App\Enums\Permission;
use App\Models\Post;
use App\Models\PostComment;
use App\Models\Section;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Course/section discussion groups. A section is the "group"; membership is
 * derived from enrolment (students) and teaching (faculty). Faculty who teach a
 * section — and admins — can moderate it.
 */
class DiscussionService
{
    /**
     * Sections the user can participate in, as {id,label} option rows.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function accessibleSections(User $user): Collection
    {
        $sections = $user->isAdmin()
            ? Section::query()->with('course:id,code')->get()
            : $this->sectionsFor($user);

        return $sections
            ->map(fn (Section $section) => [
                'id' => $section->id,
                'label' => trim(($section->course?->code ?? 'Section').' · '.$section->label),
            ])
            ->values();
    }

    /**
     * @return Collection<int, Section>
     */
    private function sectionsFor(User $user): Collection
    {
        $ids = $user->isFaculty()
            ? $user->teachingSectionIds()
            : $user->enrolledSectionIds();

        return Section::whereIn('id', $ids)->with('course:id,code')->get();
    }

    public function canAccess(User $user, Section $section): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->isFaculty()
            ? $user->teachingSectionIds()->contains($section->id)
            : $user->enrolledSectionIds()->contains($section->id);
    }

    public function canModerate(User $user, Section $section): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->hasPermission(Permission::MODERATE_DISCUSSIONS)
            && $user->teachingSectionIds()->contains($section->id);
    }

    /**
     * Paginated feed for a section, pinned posts first.
     */
    public function feed(Section $section, User $viewer, int $perPage = 10): LengthAwarePaginator
    {
        $canModerate = $this->canModerate($viewer, $section);

        return Post::where('section_id', $section->id)
            ->with(['author:id,name,avatar', 'comments.author:id,name,avatar'])
            ->withCount('reactions')
            ->withExists(['reactions as liked_by_viewer' => fn ($query) => $query->where('user_id', $viewer->id)])
            ->orderByDesc('is_pinned')
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->through(fn (Post $post) => $this->presentPost($post, $viewer, $canModerate));
    }

    /**
     * @return array<string, mixed>
     */
    private function presentPost(Post $post, User $viewer, bool $canModerate): array
    {
        return [
            'id' => $post->id,
            'body' => $post->body,
            'isPinned' => $post->is_pinned,
            'author' => ['id' => $post->author?->id, 'name' => $post->author?->name],
            'createdAt' => $post->created_at?->diffForHumans(),
            'likeCount' => $post->reactions_count,
            'likedByViewer' => (bool) $post->liked_by_viewer,
            'canDelete' => $canModerate || $post->user_id === $viewer->id,
            'canModerate' => $canModerate,
            'comments' => $post->comments->map(fn (PostComment $comment) => [
                'id' => $comment->id,
                'body' => $comment->body,
                'author' => ['id' => $comment->author?->id, 'name' => $comment->author?->name],
                'createdAt' => $comment->created_at?->diffForHumans(),
                'canDelete' => $canModerate || $comment->user_id === $viewer->id,
            ])->values(),
        ];
    }

    public function createPost(Section $section, User $author, string $body): Post
    {
        return Post::create([
            'section_id' => $section->id,
            'user_id' => $author->id,
            'body' => $body,
        ]);
    }

    public function createComment(Post $post, User $author, string $body): PostComment
    {
        return $post->comments()->create([
            'user_id' => $author->id,
            'body' => $body,
        ]);
    }

    /**
     * Toggle the viewer's like on a post. Returns the new liked state.
     */
    public function toggleLike(Post $post, User $user): bool
    {
        $existing = $post->reactions()->where('user_id', $user->id)->where('type', 'like')->first();

        if ($existing) {
            $existing->delete();

            return false;
        }

        $post->reactions()->create(['user_id' => $user->id, 'type' => 'like']);

        return true;
    }
}
