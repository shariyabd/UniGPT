<?php

namespace Tests\Feature;

use App\Domain\User\Models\User;
use App\Models\Conversation;
use App\Models\Section;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Direct messaging between a student and a faculty member they share a section
 * with. Covers eligibility (who may start a chat), the send/fetch round-trip,
 * and participant isolation (a non-participant cannot read a conversation).
 */
class MessengerTest extends TestCase
{
    use DatabaseTransactions;

    private function student(): User
    {
        $student = User::where('email', 'student@university.edu')->first();
        if (! $student) {
            $this->markTestSkipped('Demo student not seeded.');
        }

        return $student;
    }

    /** A faculty member who actually teaches the demo student. */
    private function facultyOf(User $student): User
    {
        $section = $student->enrolledSections()
            ->wherePivotNotIn('status', ['dropped', 'pending'])
            ->with('faculty')
            ->get()
            ->first(fn (Section $s) => $s->faculty !== null);

        if (! $section || ! $section->faculty) {
            $this->markTestSkipped('Demo student has no faculty-assigned section.');
        }

        return $section->faculty;
    }

    public function test_a_shared_section_makes_a_student_and_faculty_eligible(): void
    {
        $student = $this->student();
        $faculty = $this->facultyOf($student);

        $this->assertTrue($student->canMessage($faculty));
        $this->assertTrue($faculty->canMessage($student));
    }

    public function test_two_students_are_never_eligible_to_message(): void
    {
        $student = $this->student();
        $otherStudent = User::where('email', '!=', $student->email)
            ->get()
            ->first(fn (User $u) => $u->isStudent());

        if (! $otherStudent) {
            $this->markTestSkipped('Need a second student.');
        }

        $this->assertFalse($student->canMessage($otherStudent));
        $this->assertFalse($student->canMessage($student));
    }

    public function test_student_can_open_send_and_read_a_conversation_with_their_faculty(): void
    {
        $student = $this->student();
        $faculty = $this->facultyOf($student);

        // Open (resolve-or-create) the conversation.
        $resolve = $this->actingAs($student)
            ->postJson(route('messenger.conversations.resolve'), ['with' => $faculty->id])
            ->assertOk()
            ->assertJsonStructure(['conversation_id', 'messages']);

        $conversationId = $resolve->json('conversation_id');

        // Send a message (unique body so it can't collide with real history
        // left by manual testing — tests run against the live seeded DB).
        $body = 'Hello professor '.uniqid();
        $this->actingAs($student)
            ->postJson(route('messenger.messages.store', $conversationId), ['body' => $body])
            ->assertCreated()
            ->assertJsonPath('message.body', $body)
            ->assertJsonPath('message.sender_id', $student->id);

        // The faculty participant can read it back (present, not at a fixed index).
        $this->actingAs($faculty)
            ->getJson(route('messenger.messages.index', $conversationId))
            ->assertOk()
            ->assertJsonFragment(['body' => $body]);
    }

    public function test_resolving_a_conversation_is_idempotent(): void
    {
        $student = $this->student();
        $faculty = $this->facultyOf($student);

        $first = $this->actingAs($student)
            ->postJson(route('messenger.conversations.resolve'), ['with' => $faculty->id])
            ->json('conversation_id');

        $second = $this->actingAs($student)
            ->postJson(route('messenger.conversations.resolve'), ['with' => $faculty->id])
            ->json('conversation_id');

        $this->assertSame($first, $second);
    }

    public function test_student_cannot_message_a_faculty_who_does_not_teach_them(): void
    {
        $student = $this->student();
        $enrolledSectionIds = $student->enrolledSectionIds();

        // A seeded faculty member who teaches none of the student's sections.
        $stranger = User::query()->get()
            ->first(fn (User $u) => $u->isFaculty()
                && $u->teachingSectionIds()->intersect($enrolledSectionIds)->isEmpty());

        if (! $stranger) {
            $this->markTestSkipped('No faculty outside the student\'s sections to test against.');
        }

        $this->assertFalse($student->canMessage($stranger));

        $this->actingAs($student)
            ->postJson(route('messenger.conversations.resolve'), ['with' => $stranger->id])
            ->assertForbidden();
    }

    public function test_a_non_participant_cannot_read_a_conversation(): void
    {
        $student = $this->student();
        $faculty = $this->facultyOf($student);
        $conversation = Conversation::betweenOrCreate($student, $faculty);

        $outsider = User::where('email', '!=', $student->email)
            ->get()
            ->first(fn (User $u) => $u->isStudent() && $u->id !== $student->id);

        if (! $outsider) {
            $this->markTestSkipped('Need a second student.');
        }

        $this->actingAs($outsider)
            ->getJson(route('messenger.messages.index', $conversation->id))
            ->assertForbidden();
    }

    public function test_heartbeat_marks_the_user_active(): void
    {
        $student = $this->student();

        $this->actingAs($student)->postJson(route('heartbeat'))->assertOk();

        $this->assertTrue($student->fresh()->isActive());
    }

    public function test_overview_reports_unread_and_clears_on_read(): void
    {
        $student = $this->student();
        $faculty = $this->facultyOf($student);
        $conversation = Conversation::betweenOrCreate($student, $faculty);

        // Student sends → counts as unread for the faculty recipient.
        $conversation->messages()->create([
            'sender_id' => $student->id,
            'body' => 'unread test '.uniqid(),
        ]);

        $overview = $this->actingAs($faculty)
            ->getJson(route('messenger.overview', ['ids' => $student->id]))
            ->assertOk()
            ->json();

        $row = collect($overview['conversations'])->firstWhere('user_id', $student->id);
        $this->assertNotNull($row);
        $this->assertGreaterThan(0, $row['unread']);

        // Reading the conversation clears the unread count.
        $this->actingAs($faculty)
            ->postJson(route('messenger.messages.read', $conversation->id))
            ->assertOk();

        $cleared = collect(
            $this->actingAs($faculty)
                ->getJson(route('messenger.overview', ['ids' => $student->id]))
                ->json('conversations')
        )->firstWhere('user_id', $student->id);

        $this->assertSame(0, $cleared['unread']);
    }

    public function test_overview_presence_reflects_recent_heartbeat(): void
    {
        $student = $this->student();
        $faculty = $this->facultyOf($student);

        User::whereKey($faculty->id)->update(['last_seen_at' => now()]);
        $presence = $this->actingAs($student)
            ->getJson(route('messenger.overview', ['ids' => $faculty->id]))
            ->json('presence');
        $this->assertTrue($presence[(string) $faculty->id]);

        // A stale heartbeat (older than the active window) reads as offline.
        User::whereKey($faculty->id)->update(['last_seen_at' => now()->subMinutes(10)]);
        $presence = $this->actingAs($student)
            ->getJson(route('messenger.overview', ['ids' => $faculty->id]))
            ->json('presence');
        $this->assertFalse($presence[(string) $faculty->id]);
    }
}
