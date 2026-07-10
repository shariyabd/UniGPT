<?php

declare(strict_types=1);

namespace App\Http\Controllers\Faculty;

use App\Domain\Academic\Services\OfficeHoursService;
use App\Http\Controllers\Controller;
use App\Models\OfficeHourSlot;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Faculty side of office hours: publish/remove slots, see and manage the
 * students who booked them.
 */
class OfficeHoursController extends Controller
{
    public function __construct(private readonly OfficeHoursService $officeHours) {}

    public function index(Request $request): Response
    {
        return Inertia::render('Faculty/OfficeHours', [
            'slots' => $this->officeHours->slotsForFaculty($request->user()),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'starts_at' => ['required', 'date', 'after:now'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'location' => ['nullable', 'string', 'max:120'],
            'note' => ['nullable', 'string', 'max:200'],
        ]);

        $this->officeHours->create($request->user(), $validated);

        return back()->with('success', 'Office-hours slot published.');
    }

    public function destroy(Request $request, OfficeHourSlot $slot): RedirectResponse
    {
        $this->officeHours->delete($request->user(), $slot);

        return back()->with('success', 'Slot removed.');
    }

    public function cancelBooking(Request $request, OfficeHourSlot $slot): RedirectResponse
    {
        $this->officeHours->cancelBooking($request->user(), $slot);

        return back()->with('success', 'Booking cancelled — the slot is open again.');
    }
}
