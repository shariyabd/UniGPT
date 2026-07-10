<?php

declare(strict_types=1);

namespace App\Domain\Community\Services;

use App\Domain\User\Models\User;
use App\Models\Conversation;
use Illuminate\Support\Collection;

/**
 * Group study rooms: section-scoped group conversations between classmates,
 * layered on the existing messenger plumbing (same messages table, same
 * realtime channel + polling fallback, same participant-based authorization).
 * A room is a Conversation of TYPE_GROUP; membership is the participants
 * pivot; only students actively enrolled in the room's section may join.
 */
class StudyRoomService
{
    /**
     * Every room in the student's enrolled sections, joined rooms first.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function roomsFor(User $student): Collection
    {
        $sectionIds = $student->enrolledSectionIds();
        if ($sectionIds->isEmpty()) {
            return collect();
        }

        return Conversation::query()
            ->group()
            ->whereIn('section_id', $sectionIds)
            ->with(['section.course:id,code', 'creator:id,name'])
            ->withCount('participants')
            ->withExists(['participants as is_member' => fn ($q) => $q->whereKey($student->id)])
            ->orderByDesc('last_message_at')
            ->orderByDesc('id')
            ->get()
            ->map(fn (Conversation $room) => [
                'id' => $room->id,
                'title' => $room->title,
                'section' => trim(($room->section?->course?->code ?? '').' — Section '.($room->section?->label ?? '')),
                'members' => $room->participants_count,
                'isMember' => (bool) $room->is_member,
                'createdBy' => $room->creator?->name,
                'lastMessageAt' => $room->last_message_at?->diffForHumans(),
            ])
            ->sortByDesc('isMember')
            ->values();
    }

    /**
     * Sections the student can create rooms in, labelled for the form.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function sectionsFor(User $student): Collection
    {
        return $student->enrolledSections()
            ->wherePivotNotIn('status', ['dropped', 'pending'])
            ->with('course:id,code,name')
            ->get()
            ->unique('id')
            ->map(fn ($section) => [
                'id' => $section->id,
                'label' => trim(($section->course?->code ?? '').' — Section '.$section->label),
            ])
            ->values();
    }

    /**
     * Create a room in one of the student's sections; the creator is its
     * first member.
     *
     * @param  array{section_id: int, title: string}  $data
     */
    public function create(User $student, array $data): Conversation
    {
        abort_unless(
            $student->enrolledSectionIds()->contains((int) $data['section_id']),
            403,
            'You can only create study rooms in sections you are enrolled in.',
        );

        $room = Conversation::create([
            'type' => Conversation::TYPE_GROUP,
            'title' => $data['title'],
            'section_id' => (int) $data['section_id'],
            'created_by' => $student->id,
        ]);

        $room->participants()->attach($student->id);

        return $room;
    }

    /**
     * Join a room — only for students enrolled in the room's section.
     */
    public function join(User $student, Conversation $room): void
    {
        abort_unless($room->isGroup(), 404);
        abort_unless(
            $student->enrolledSectionIds()->contains((int) $room->section_id),
            403,
            'You can only join study rooms of sections you are enrolled in.',
        );

        $room->participants()->syncWithoutDetaching([$student->id]);
    }

    /**
     * Leave a room. The last member out deletes the room (messages cascade).
     */
    public function leave(User $student, Conversation $room): void
    {
        abort_unless($room->isGroup(), 404);

        $room->participants()->detach($student->id);

        if ($room->participants()->count() === 0) {
            $room->delete();
        }
    }

    /**
     * Member directory for a room the user belongs to — the client resolves
     * message sender names from this map.
     *
     * @return Collection<int, array{id: int, name: string}>
     */
    public function members(Conversation $room): Collection
    {
        return $room->participants()
            ->get(['users.id', 'users.name'])
            ->map(fn (User $member) => ['id' => $member->id, 'name' => $member->name]);
    }
}
