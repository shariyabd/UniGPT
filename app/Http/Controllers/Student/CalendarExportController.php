<?php

declare(strict_types=1);

namespace App\Http\Controllers\Student;

use App\Domain\Academic\Services\IcsExportService;
use App\Domain\User\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * iCalendar export of the student's unified calendar. Two entry points:
 *
 *  - download: authenticated, saves a one-off .ics file.
 *  - feed: a *signed* URL (no session) so external calendar apps — Google
 *    Calendar, Outlook, Apple Calendar — can subscribe and stay in sync.
 *    The signature is the credential; tampering with {user} invalidates it.
 */
class CalendarExportController extends Controller
{
    public function __construct(private readonly IcsExportService $ics) {}

    public function download(Request $request): Response
    {
        return $this->icsResponse($this->ics->forStudent($request->user()), 'attachment');
    }

    public function feed(User $user): Response
    {
        return $this->icsResponse($this->ics->forStudent($user), 'inline');
    }

    private function icsResponse(string $body, string $disposition): Response
    {
        return response($body, 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => $disposition.'; filename="uninexus-calendar.ics"',
            'Cache-Control' => 'no-cache',
        ]);
    }
}
