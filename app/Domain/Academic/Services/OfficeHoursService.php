<?php

declare(strict_types=1);

namespace App\Domain\Academic\Services;

use App\Domain\Notification\Services\NotificationService;
use App\Domain\User\Models\User;
use App\Enums\NotificationType;
use App\Models\OfficeHourSlot;
use App\Models\Section;
use Illuminate\Support\Collection;

/**
 * Office-hours booking: faculty publish single-capacity time slots; students
 * of their sections book them. Booking is atomic (first click wins) and both
 * sides get in-app notifications on book/cancel.
 */
class OfficeHoursService
{
    public function __construct(private readonly NotificationService $notifications) {}

    /**
     * A faculty member's upcoming slots, with booking info.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function slotsForFaculty(User $faculty): Collection
    {
        return OfficeHourSlot::query()
            ->where('faculty_id', $faculty->id)
            ->upcoming()
            ->with('student:id,name,student_id')
            ->get()
            ->map(fn (OfficeHourSlot $slot) => $this->present($slot) + [
                'student' => $slot->student?->only(['id', 'name', 'student_id']),
            ]);
    }

    /**
     * Publish a slot.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(User $faculty, array $data): OfficeHourSlot
    {
        return OfficeHourSlot::create([
            'faculty_id' => $faculty->id,
            'starts_at' => $data['starts_at'],
            'ends_at' => $data['ends_at'],
            'location' => $data['location'] ?? null,
            'note' => $data['note'] ?? null,
        ]);
    }

    /**
     * Remove a slot; a booked student is notified their meeting is off.
     */
    public function delete(User $faculty, OfficeHourSlot $slot): void
    {
        abort_unless($slot->faculty_id === $faculty->id, 403);

        if ($slot->student !== null) {
            $this->notifications->notify(
                user: $slot->student,
                type: NotificationType::OFFICE_HOURS,
                title: 'Office hours cancelled',
                message: "{$faculty->name} cancelled the {$slot->starts_at->format('M j, g:i A')} slot.",
                link: route('office-hours'),
            );
        }

        $slot->delete();
    }

    /**
     * What a student sees: upcoming slots of the faculty teaching their
     * sections (open ones plus their own bookings).
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function slotsForStudent(User $student): Collection
    {
        $facultyIds = $this->facultyIdsFor($student);
        if ($facultyIds->isEmpty()) {
            return collect();
        }

        return OfficeHourSlot::query()
            ->whereIn('faculty_id', $facultyIds)
            ->upcoming()
            ->where(fn ($q) => $q->whereNull('booked_by')->orWhere('booked_by', $student->id))
            ->with('faculty:id,name')
            ->get()
            ->map(fn (OfficeHourSlot $slot) => $this->present($slot) + [
                'faculty' => $slot->faculty?->only(['id', 'name']),
                'isMine' => $slot->booked_by === $student->id,
            ]);
    }

    /**
     * Book a slot for a student. Atomic: the UPDATE only lands when the slot
     * is still open, so two simultaneous clicks can never double-book.
     */
    public function book(User $student, OfficeHourSlot $slot): void
    {
        abort_unless(
            $this->facultyIdsFor($student)->contains($slot->faculty_id),
            403,
            'You can only book office hours with faculty who teach you.',
        );
        abort_if($slot->starts_at->isPast(), 422, 'This slot is in the past.');

        $claimed = OfficeHourSlot::whereKey($slot->id)
            ->whereNull('booked_by')
            ->update(['booked_by' => $student->id, 'booked_at' => now()]);

        abort_unless($claimed === 1, 409, 'This slot was just booked by someone else.');

        $this->notifications->notify(
            user: $slot->faculty,
            type: NotificationType::OFFICE_HOURS,
            title: 'Office hours booked',
            message: "{$student->name} booked your {$slot->starts_at->format('M j, g:i A')} slot.",
            link: route('faculty.office-hours'),
        );
    }

    /**
     * Cancel a booking — by the student who holds it, or by the slot's
     * faculty (which re-opens the slot). The other party is notified.
     */
    public function cancelBooking(User $actor, OfficeHourSlot $slot): void
    {
        abort_unless($slot->isBooked(), 404);

        $isStudent = $slot->booked_by === $actor->id;
        $isFaculty = $slot->faculty_id === $actor->id;
        abort_unless($isStudent || $isFaculty, 403);

        $student = $slot->student;
        $slot->forceFill(['booked_by' => null, 'booked_at' => null])->save();

        if ($isStudent) {
            $this->notifications->notify(
                user: $slot->faculty,
                type: NotificationType::OFFICE_HOURS,
                title: 'Office hours booking cancelled',
                message: "{$actor->name} cancelled the {$slot->starts_at->format('M j, g:i A')} booking.",
                link: route('faculty.office-hours'),
            );
        } elseif ($student !== null) {
            $this->notifications->notify(
                user: $student,
                type: NotificationType::OFFICE_HOURS,
                title: 'Office hours booking cancelled',
                message: "{$actor->name} cancelled your {$slot->starts_at->format('M j, g:i A')} booking.",
                link: route('office-hours'),
            );
        }
    }

    /**
     * A student's upcoming booked meetings, for the calendar.
     *
     * @return Collection<int, OfficeHourSlot>
     */
    public function bookingsFor(User $student): Collection
    {
        return OfficeHourSlot::query()
            ->where('booked_by', $student->id)
            ->with('faculty:id,name')
            ->orderBy('starts_at')
            ->get();
    }

    /**
     * IDs of the faculty currently teaching this student's sections.
     *
     * @return Collection<int, int>
     */
    private function facultyIdsFor(User $student): Collection
    {
        $sectionIds = $student->enrolledSectionIds();
        if ($sectionIds->isEmpty()) {
            return collect();
        }

        return Section::whereIn('id', $sectionIds)
            ->whereNotNull('faculty_id')
            ->pluck('faculty_id')
            ->unique()
            ->values();
    }

    /**
     * @return array<string, mixed>
     */
    private function present(OfficeHourSlot $slot): array
    {
        return [
            'id' => $slot->id,
            'date' => $slot->starts_at->format('D, M j'),
            'start' => $slot->starts_at->format('g:i A'),
            'end' => $slot->ends_at->format('g:i A'),
            'location' => $slot->location,
            'note' => $slot->note,
            'isBooked' => $slot->isBooked(),
        ];
    }
}
