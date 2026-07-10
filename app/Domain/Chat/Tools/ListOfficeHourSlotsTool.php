<?php

declare(strict_types=1);

namespace App\Domain\Chat\Tools;

use App\Domain\Academic\Services\OfficeHoursService;
use App\Domain\Chat\Contracts\ChatToolInterface;
use App\Domain\User\Models\User;

/**
 * Upcoming office-hour slots of the faculty who teach this student —
 * open slots plus the student's own bookings.
 */
class ListOfficeHourSlotsTool implements ChatToolInterface
{
    public function __construct(private readonly OfficeHoursService $officeHours) {}

    public function name(): string
    {
        return 'list_office_hour_slots';
    }

    public function label(): string
    {
        return 'Checking office-hour slots';
    }

    public function description(): string
    {
        return 'List upcoming office-hour slots published by the faculty who teach the student, including '
            .'each slot\'s numeric id, faculty name, date, time, location and whether the student already '
            .'booked it (isMine). Always call this before booking so you use a real slot id.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => (object) [],
            'required' => [],
        ];
    }

    public function execute(User $user, array $arguments): array
    {
        $slots = $this->officeHours->slotsForStudent($user)->values()->all();

        return [
            'data' => ['slots' => $slots],
            'summary' => $slots === []
                ? 'No upcoming office-hour slots'
                : count($slots).' slot'.(count($slots) === 1 ? '' : 's').' found',
            'link' => route('office-hours'),
            'linkLabel' => 'Open office hours',
        ];
    }
}
