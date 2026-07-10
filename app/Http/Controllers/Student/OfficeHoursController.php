<?php

declare(strict_types=1);

namespace App\Http\Controllers\Student;

use App\Domain\Academic\Services\OfficeHoursService;
use App\Http\Controllers\Controller;
use App\Models\OfficeHourSlot;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Student side of office hours: browse their faculty's open slots, book one,
 * cancel their own booking.
 */
class OfficeHoursController extends Controller
{
    public function __construct(private readonly OfficeHoursService $officeHours) {}

    public function index(Request $request): Response
    {
        return Inertia::render('Student/OfficeHours', [
            'slots' => $this->officeHours->slotsForStudent($request->user()),
        ]);
    }

    public function book(Request $request, OfficeHourSlot $slot): RedirectResponse
    {
        $this->officeHours->book($request->user(), $slot);

        return back()->with('success', 'Slot booked — see you there!');
    }

    public function cancel(Request $request, OfficeHourSlot $slot): RedirectResponse
    {
        $this->officeHours->cancelBooking($request->user(), $slot);

        return back()->with('success', 'Booking cancelled.');
    }
}
