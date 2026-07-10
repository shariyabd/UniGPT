<?php

declare(strict_types=1);

namespace App\Domain\Search;

use App\Domain\RAG\Retrieval\RetrievalService;
use App\Domain\User\Models\User;
use App\Models\Assignment;
use App\Models\ChatMessage;
use App\Models\Course;
use App\Models\Document;
use App\Models\Post;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Global search behind the ⌘K palette. Two layers:
 *
 *  - "Knowledge" — semantic search over the user's RAG corpus (approved
 *    library documents, own notes, section materials) via the existing
 *    embedding retrieval, so results match by meaning, not just keywords.
 *  - Entity groups — fast lexical lookups over the records the user can
 *    already access (courses, assignments, discussions, own chat history).
 *
 * Every group is capped so the palette stays a palette, not a report.
 */
class GlobalSearchService
{
    private const PER_GROUP = 5;

    public function __construct(private readonly RetrievalService $retrieval) {}

    /**
     * @return array<int, array{label: string, items: array<int, array{title: string, snippet: ?string, badge: ?string, url: string}>}>
     */
    public function search(User $user, string $query): array
    {
        $query = trim($query);
        if ($query === '') {
            return [];
        }

        $groups = collect([
            ['label' => 'Knowledge', 'items' => $this->knowledge($user, $query)],
            ['label' => 'Courses', 'items' => $this->courses($user, $query)],
            ['label' => 'Assignments', 'items' => $this->assignments($user, $query)],
            ['label' => 'Discussions', 'items' => $this->discussions($user, $query)],
            ['label' => 'Chat History', 'items' => $this->chatHistory($user, $query)],
        ]);

        if ($user->isAdmin()) {
            $groups->push(['label' => 'Users', 'items' => $this->users($query)]);
        }

        return $groups->filter(fn (array $group) => $group['items'] !== [])->values()->all();
    }

    /**
     * Semantic hits across the user's retrievable corpus, linked to the page
     * where that kind of content lives.
     *
     * @return array<int, array<string, mixed>>
     */
    private function knowledge(User $user, string $query): array
    {
        return $this->retrieval->retrieve($query, $user, self::PER_GROUP)
            ->map(function (array $row) use ($user) {
                /** @var Document $document */
                $document = $row['document'];

                return [
                    'title' => $document->title,
                    'snippet' => Str::limit(trim(preg_replace('/\s+/', ' ', $row['chunk']->content) ?? ''), 110),
                    'badge' => $document->category,
                    'url' => $this->knowledgeUrl($user, $document),
                ];
            })
            ->values()
            ->all();
    }

    private function knowledgeUrl(User $user, Document $document): string
    {
        if ($document->source_type === Document::SOURCE_NOTE) {
            return route('notes');
        }

        if ($document->source_type === Document::SOURCE_MATERIAL) {
            return $user->isFaculty() ? route('faculty.courses') : route('materials');
        }

        return $user->isAdmin() ? route('admin.documents') : route('documents');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function courses(User $user, string $query): array
    {
        $like = '%'.$query.'%';

        $builder = match (true) {
            $user->isAdmin() => Course::query(),
            $user->isFaculty() => Course::whereIn('id', $user->teachingSections()->pluck('course_id')),
            default => Course::whereIn('id', $user->enrolledCourses()->wherePivotNotIn('status', ['pending'])->pluck('courses.id')),
        };

        return $builder
            ->where(fn (Builder $q) => $q->where('courses.code', 'like', $like)->orWhere('courses.name', 'like', $like))
            ->limit(self::PER_GROUP)
            ->get(['courses.id', 'courses.code', 'courses.name'])
            ->unique('id')
            ->map(fn (Course $course) => [
                'title' => trim($course->code.' — '.$course->name),
                'snippet' => null,
                'badge' => 'Course',
                'url' => $user->isFaculty()
                    ? route('faculty.courses.show', $course->id)
                    : ($user->isAdmin() ? route('admin.courses') : route('materials')),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function assignments(User $user, string $query): array
    {
        $sectionIds = $this->sectionIds($user);
        if ($sectionIds->isEmpty()) {
            return [];
        }

        return Assignment::query()
            ->whereIn('section_id', $sectionIds)
            ->where('status', 'published')
            ->where('title', 'like', '%'.$query.'%')
            ->latest('due_at')
            ->limit(self::PER_GROUP)
            ->get()
            ->map(fn (Assignment $assignment) => [
                'title' => $assignment->title,
                'snippet' => $assignment->due_at ? 'Due '.$assignment->due_at->format('M j, Y') : null,
                'badge' => 'Assignment',
                'url' => $user->isFaculty() ? route('faculty.grading') : route('assignments.show', $assignment->id),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function discussions(User $user, string $query): array
    {
        $sectionIds = $this->sectionIds($user);
        if ($sectionIds->isEmpty()) {
            return [];
        }

        return Post::query()
            ->whereIn('section_id', $sectionIds)
            ->where('body', 'like', '%'.$query.'%')
            ->with('user:id,name')
            ->latest()
            ->limit(self::PER_GROUP)
            ->get()
            ->map(fn (Post $post) => [
                'title' => Str::limit(trim((string) $post->body), 70),
                'snippet' => $post->user?->name,
                'badge' => 'Discussion',
                'url' => route('discussions.index'),
            ])
            ->values()
            ->all();
    }

    /**
     * The user's own chat history, one hit per session, deep-linked to the
     * exact message.
     *
     * @return array<int, array<string, mixed>>
     */
    private function chatHistory(User $user, string $query): array
    {
        return ChatMessage::query()
            ->whereHas('session', fn (Builder $q) => $q->where('user_id', $user->id))
            ->where('content', 'like', '%'.$query.'%')
            ->latest()
            ->limit(25)
            ->get()
            ->unique('chat_session_id')
            ->take(self::PER_GROUP)
            ->map(fn (ChatMessage $message) => [
                'title' => Str::limit(trim($message->content), 70),
                'snippet' => $message->created_at?->diffForHumans(),
                'badge' => 'Chat',
                'url' => ($user->isFaculty() ? route('faculty.ai-assistant') : route('chat'))
                    .'?session='.$message->chat_session_id.'&message='.$message->id,
            ])
            ->values()
            ->all();
    }

    /**
     * Admin-only people lookup.
     *
     * @return array<int, array<string, mixed>>
     */
    private function users(string $query): array
    {
        $like = '%'.$query.'%';

        return User::query()
            ->where(fn (Builder $q) => $q->where('name', 'like', $like)->orWhere('email', 'like', $like))
            ->limit(self::PER_GROUP)
            ->get(['id', 'name', 'email'])
            ->map(fn (User $user) => [
                'title' => $user->name,
                'snippet' => $user->email,
                'badge' => 'User',
                'url' => route('admin.users'),
            ])
            ->values()
            ->all();
    }

    /**
     * @return Collection<int, int>
     */
    private function sectionIds(User $user): Collection
    {
        return match (true) {
            $user->isStudent() => $user->enrolledSectionIds(),
            $user->isFaculty() => $user->teachingSectionIds(),
            default => collect(),
        };
    }
}
