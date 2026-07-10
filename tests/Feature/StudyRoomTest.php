<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\User\Models\User;
use App\Models\Conversation;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Group study rooms: section-scoped group conversations layered on the
 * messenger — creation, membership, chat access, and isolation from both
 * other sections and the 1:1 direct-message surface.
 */
class StudyRoomTest extends TestCase
{
    use DatabaseTransactions;

    private function student(): User
    {
        $student = User::where('email', 'student@university.edu')->first();
        if (! $student) {
            $this->markTestSkipped('Demo student not seeded; run php artisan db:seed.');
        }
        if ($student->enrolledSectionIds()->isEmpty()) {
            $this->markTestSkipped('Demo student has no enrolled sections.');
        }

        return $student;
    }

    /**
     * A student who shares no section with the given one.
     */
    private function outsider(User $not): User
    {
        $sectionIds = $not->enrolledSectionIds();

        $outsider = User::where('id', '!=', $not->id)
            ->whereHas('roles', fn ($q) => $q->where('slug', 'student'))
            ->get()
            ->first(fn (User $candidate) => $candidate->enrolledSectionIds()->intersect($sectionIds)->isEmpty());

        if (! $outsider) {
            $this->markTestSkipped('No student without shared sections seeded.');
        }

        return $outsider;
    }

    /**
     * A section of the student's that has at least one other enrolled student,
     * plus that classmate.
     *
     * @return array{0: int, 1: User}
     */
    private function sectionWithClassmate(User $of): array
    {
        foreach ($of->enrolledSectionIds() as $sectionId) {
            $classmate = User::where('id', '!=', $of->id)
                ->whereHas('roles', fn ($q) => $q->where('slug', 'student'))
                ->whereHas('enrolledSections', fn ($q) => $q
                    ->where('sections.id', $sectionId)
                    ->whereNotIn('course_user.status', ['dropped', 'pending']))
                ->first();

            if ($classmate) {
                return [(int) $sectionId, $classmate];
            }
        }

        $this->markTestSkipped('No classmate shares a section with the demo student.');
    }

    private function makeRoom(User $owner, ?int $sectionId = null): Conversation
    {
        $this->actingAs($owner)->post('/study-rooms', [
            'title' => 'Test Study Room',
            'section_id' => $sectionId ?? $owner->enrolledSectionIds()->first(),
        ])->assertRedirect();

        return Conversation::query()->group()->where('created_by', $owner->id)->latest('id')->firstOrFail();
    }

    public function test_student_can_create_a_room_in_their_section_and_becomes_member(): void
    {
        $student = $this->student();
        $room = $this->makeRoom($student);

        $this->assertSame(Conversation::TYPE_GROUP, $room->type);
        $this->assertTrue($room->isParticipant($student));
    }

    public function test_student_cannot_create_a_room_in_a_foreign_section(): void
    {
        $student = $this->student();
        $outsider = $this->outsider($student);

        $this->actingAs($outsider)->post('/study-rooms', [
            'title' => 'Sneaky room',
            'section_id' => $student->enrolledSectionIds()->first(),
        ])->assertForbidden();
    }

    public function test_classmate_can_join_and_chat_in_the_room(): void
    {
        $student = $this->student();
        [$sectionId, $classmate] = $this->sectionWithClassmate($student);

        $room = $this->makeRoom($student, $sectionId);

        $this->actingAs($classmate)->post("/study-rooms/{$room->id}/join")->assertRedirect();
        $this->assertTrue($room->isParticipant($classmate));

        // Chat runs on the shared messenger endpoints, membership-authorized.
        $this->actingAs($classmate)
            ->postJson("/messenger/conversations/{$room->id}/messages", ['body' => 'Anyone up for revising chapter 4?'])
            ->assertCreated();

        $this->actingAs($student)
            ->getJson("/messenger/conversations/{$room->id}/messages")
            ->assertOk()
            ->assertJsonFragment(['body' => 'Anyone up for revising chapter 4?']);
    }

    public function test_outsider_cannot_join_or_read_the_room(): void
    {
        $student = $this->student();
        $outsider = $this->outsider($student);
        $room = $this->makeRoom($student);

        $this->actingAs($outsider)->post("/study-rooms/{$room->id}/join")->assertForbidden();
        $this->actingAs($outsider)->getJson("/messenger/conversations/{$room->id}/messages")->assertForbidden();
    }

    public function test_last_member_leaving_deletes_the_room(): void
    {
        $student = $this->student();
        $room = $this->makeRoom($student);

        $this->actingAs($student)->post("/study-rooms/{$room->id}/leave")->assertRedirect();

        $this->assertNull(Conversation::query()->group()->find($room->id));
    }

    public function test_group_rooms_never_leak_into_the_direct_message_surface(): void
    {
        $student = $this->student();
        [$sectionId, $classmate] = $this->sectionWithClassmate($student);

        $room = $this->makeRoom($student, $sectionId);
        $this->actingAs($classmate)->post("/study-rooms/{$room->id}/join")->assertRedirect();

        // The messenger overview must not list the room as a contact thread.
        $overview = $this->actingAs($student)->getJson('/messenger/overview')->json('conversations');
        $this->assertNotContains(
            $classmate->id,
            array_column($overview ?? [], 'user_id'),
            'A study room must not appear as a 1:1 conversation.',
        );

        // And resolving a direct chat must never reuse the group conversation
        // just because both users are members of it.
        $faculty = User::where('email', 'prof.smith@university.edu')->first();
        if ($faculty && $student->canMessage($faculty)) {
            $direct = Conversation::betweenOrCreate($student, $faculty);
            $this->assertSame(Conversation::TYPE_DIRECT, $direct->type);
            $this->assertNotSame($room->id, $direct->id);
        }
    }

    public function test_members_endpoint_is_member_only(): void
    {
        $student = $this->student();
        $outsider = $this->outsider($student);
        $room = $this->makeRoom($student);

        $this->actingAs($student)
            ->getJson("/study-rooms/{$room->id}/members")
            ->assertOk()
            ->assertJsonFragment(['name' => $student->name]);

        $this->actingAs($outsider)
            ->getJson("/study-rooms/{$room->id}/members")
            ->assertForbidden();
    }
}
