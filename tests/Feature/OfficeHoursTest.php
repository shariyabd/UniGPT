<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Academic\Services\CalendarService;
use App\Domain\User\Models\User;
use App\Models\Notification;
use App\Models\OfficeHourSlot;
use App\Models\Section;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Office-hours booking: faculty publish single-capacity slots; students of
 * their sections book/cancel them atomically, with notifications both ways
 * and booked meetings feeding the student calendar.
 */
class OfficeHoursTest extends TestCase
{
    use DatabaseTransactions;

    private function student(): User
    {
        $student = User::where('email', 'student@university.edu')->first();
        if (! $student || $student->enrolledSectionIds()->isEmpty()) {
            $this->markTestSkipped('Demo student not seeded or has no sections.');
        }

        return $student;
    }

    /**
     * A faculty member actually teaching one of the student's sections.
     */
    private function facultyOf(User $student): User
    {
        $facultyId = Section::whereIn('id', $student->enrolledSectionIds())
            ->whereNotNull('faculty_id')
            ->value('faculty_id');

        if (! $facultyId) {
            $this->markTestSkipped('No faculty teaches the demo student.');
        }

        return User::findOrFail($facultyId);
    }

    private function makeSlot(User $faculty): OfficeHourSlot
    {
        $this->actingAs($faculty)->post('/faculty/office-hours', [
            'starts_at' => now()->addDays(2)->setTime(14, 0)->format('Y-m-d H:i:s'),
            'ends_at' => now()->addDays(2)->setTime(14, 30)->format('Y-m-d H:i:s'),
            'location' => 'Room 402',
        ])->assertRedirect();

        return OfficeHourSlot::where('faculty_id', $faculty->id)->latest('id')->firstOrFail();
    }

    public function test_faculty_can_publish_and_remove_slots(): void
    {
        $faculty = $this->facultyOf($this->student());
        $slot = $this->makeSlot($faculty);

        $this->assertFalse($slot->isBooked());

        $this->actingAs($faculty)->delete("/faculty/office-hours/{$slot->id}")->assertRedirect();
        $this->assertNull(OfficeHourSlot::find($slot->id));
    }

    public function test_student_can_book_a_slot_and_faculty_is_notified(): void
    {
        $student = $this->student();
        $faculty = $this->facultyOf($student);
        $slot = $this->makeSlot($faculty);

        $this->actingAs($student)->post("/office-hours/{$slot->id}/book")->assertRedirect();

        $slot->refresh();
        $this->assertSame($student->id, $slot->booked_by);
        $this->assertTrue(
            Notification::where('user_id', $faculty->id)->where('type', 'office_hours')->exists(),
            'Faculty should be notified of the booking.',
        );
    }

    public function test_booking_is_atomic_and_double_booking_conflicts(): void
    {
        $student = $this->student();
        $faculty = $this->facultyOf($student);
        $slot = $this->makeSlot($faculty);

        // Another of the faculty's students grabs the slot first.
        $rival = User::where('id', '!=', $student->id)
            ->whereHas('roles', fn ($q) => $q->where('slug', 'student'))
            ->whereHas('enrolledSections', fn ($q) => $q->where('sections.faculty_id', $faculty->id)
                ->whereNotIn('course_user.status', ['dropped', 'pending']))
            ->first();

        if (! $rival) {
            $this->markTestSkipped('No second student of this faculty seeded.');
        }

        $this->actingAs($rival)->post("/office-hours/{$slot->id}/book")->assertRedirect();

        $this->actingAs($student)
            ->postJson("/office-hours/{$slot->id}/book")
            ->assertStatus(409);

        $this->assertSame($rival->id, $slot->fresh()->booked_by);
    }

    public function test_stranger_cannot_book_anothers_faculty_slot(): void
    {
        $student = $this->student();
        $faculty = $this->facultyOf($student);
        $slot = $this->makeSlot($faculty);

        // A student none of whose sections are taught by this faculty.
        $stranger = User::where('id', '!=', $student->id)
            ->whereHas('roles', fn ($q) => $q->where('slug', 'student'))
            ->get()
            ->first(fn (User $candidate) => Section::whereIn('id', $candidate->enrolledSectionIds())
                ->where('faculty_id', $faculty->id)
                ->doesntExist());

        if (! $stranger) {
            $this->markTestSkipped('No unrelated student seeded.');
        }

        $this->actingAs($stranger)->postJson("/office-hours/{$slot->id}/book")->assertForbidden();
    }

    public function test_student_cancel_reopens_slot_and_notifies_faculty(): void
    {
        $student = $this->student();
        $faculty = $this->facultyOf($student);
        $slot = $this->makeSlot($faculty);

        $this->actingAs($student)->post("/office-hours/{$slot->id}/book")->assertRedirect();
        $this->actingAs($student)->post("/office-hours/{$slot->id}/cancel")->assertRedirect();

        $this->assertFalse($slot->fresh()->isBooked());
        $this->assertSame(
            2,
            Notification::where('user_id', $faculty->id)->where('type', 'office_hours')->count(),
            'Faculty should be notified of booking and cancellation.',
        );
    }

    public function test_booked_meeting_appears_on_the_student_calendar(): void
    {
        $student = $this->student();
        $faculty = $this->facultyOf($student);
        $slot = $this->makeSlot($faculty);

        $this->actingAs($student)->post("/office-hours/{$slot->id}/book")->assertRedirect();

        $events = app(CalendarService::class)->build($student)['events'];
        $ids = array_column($events, 'id');

        $this->assertContains('office-hours-'.$slot->id, $ids);
    }
}
