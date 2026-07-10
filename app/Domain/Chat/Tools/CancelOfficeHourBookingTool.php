<?php

declare(strict_types=1);

namespace App\Domain\Chat\Tools;

use App\Domain\Academic\Services\OfficeHoursService;
use App\Domain\Chat\Contracts\ChatToolInterface;
use App\Domain\User\Models\User;
use App\Models\OfficeHourSlot;

/**
 * Cancel one of the student's own office-hour bookings. The service only
 * allows the booking holder (or the slot's faculty) to cancel.
 */
class CancelOfficeHourBookingTool implements ChatToolInterface
{
    public function __construct(private readonly OfficeHoursService $officeHours) {}

    public function name(): string
    {
        return 'cancel_office_hour_booking';
    }

    public function label(): string
    {
        return 'Cancelling office-hour booking';
    }

    public function description(): string
    {
        return 'Cancel an office-hour booking the student holds. Only call this after the student has '
            .'clearly asked to cancel that specific booking (their booked slots have isMine=true in '
            .'list_office_hour_slots).';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'slot_id' => [
                    'type' => 'integer',
                    'description' => 'Id of the booked slot to cancel, from list_office_hour_slots.',
                ],
            ],
            'required' => ['slot_id'],
        ];
    }

    public function execute(User $user, array $arguments): array
    {
        $slot = OfficeHourSlot::with('faculty:id,name')->find((int) ($arguments['slot_id'] ?? 0));
        abort_if($slot === null, 404, 'That office-hour slot no longer exists.');

        $this->officeHours->cancelBooking($user, $slot);

        $when = $slot->starts_at->format('D, M j g:i A');

        return [
            'data' => ['cancelled' => true, 'slot_id' => $slot->id, 'starts_at' => $when],
            'summary' => "Cancelled the {$when} booking with {$slot->faculty?->name}",
            'link' => route('office-hours'),
            'linkLabel' => 'Open office hours',
        ];
    }
}
