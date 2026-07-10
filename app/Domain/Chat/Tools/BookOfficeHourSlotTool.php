<?php

declare(strict_types=1);

namespace App\Domain\Chat\Tools;

use App\Domain\Academic\Services\OfficeHoursService;
use App\Domain\Chat\Contracts\ChatToolInterface;
use App\Domain\User\Models\User;
use App\Models\OfficeHourSlot;

/**
 * Book an open office-hour slot for the student. Delegates to
 * OfficeHoursService::book(), so the relationship gate (only faculty who
 * teach the student) and the atomic first-click-wins claim both apply.
 */
class BookOfficeHourSlotTool implements ChatToolInterface
{
    public function __construct(private readonly OfficeHoursService $officeHours) {}

    public function name(): string
    {
        return 'book_office_hour_slot';
    }

    public function label(): string
    {
        return 'Booking office hours';
    }

    public function description(): string
    {
        return 'Book an open office-hour slot for the student. Only call this after the student has '
            .'clearly asked to book a specific slot; use list_office_hour_slots first to find the real '
            .'slot id. Never invent slot ids.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'slot_id' => [
                    'type' => 'integer',
                    'description' => 'Id of the slot to book, from list_office_hour_slots.',
                ],
            ],
            'required' => ['slot_id'],
        ];
    }

    public function execute(User $user, array $arguments): array
    {
        $slot = OfficeHourSlot::with('faculty:id,name')->find((int) ($arguments['slot_id'] ?? 0));
        abort_if($slot === null, 404, 'That office-hour slot no longer exists.');

        $this->officeHours->book($user, $slot);

        $when = $slot->starts_at->format('D, M j g:i A');

        return [
            'data' => [
                'booked' => true,
                'slot_id' => $slot->id,
                'faculty' => $slot->faculty?->name,
                'starts_at' => $when,
                'location' => $slot->location,
            ],
            'summary' => "Booked {$slot->faculty?->name} on {$when}",
            'link' => route('office-hours'),
            'linkLabel' => 'View booking',
        ];
    }
}
