<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\User\Models\User;
use App\Models\OfficeHourSlot;
use App\Models\Section;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * iCalendar export: authenticated .ics download plus the signed subscription
 * feed external calendar apps poll without a session.
 */
class CalendarExportTest extends TestCase
{
    use DatabaseTransactions;

    private function student(): User
    {
        $student = User::where('email', 'student@university.edu')->first();
        if (! $student) {
            $this->markTestSkipped('Demo student not seeded; run php artisan db:seed.');
        }

        return $student;
    }

    public function test_student_can_download_their_calendar_as_ics(): void
    {
        $response = $this->actingAs($this->student())->get('/calendar/export');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/calendar; charset=utf-8');

        $body = $response->getContent();
        $this->assertStringStartsWith('BEGIN:VCALENDAR', $body);
        $this->assertStringContainsString('END:VCALENDAR', $body);
    }

    public function test_ics_contains_calendar_events_including_booked_office_hours(): void
    {
        $student = $this->student();

        $facultyId = Section::whereIn('id', $student->enrolledSectionIds())
            ->whereNotNull('faculty_id')
            ->value('faculty_id');
        if (! $facultyId) {
            $this->markTestSkipped('No faculty teaches the demo student.');
        }

        $slot = OfficeHourSlot::create([
            'faculty_id' => $facultyId,
            'starts_at' => now()->addDays(3)->setTime(10, 0),
            'ends_at' => now()->addDays(3)->setTime(10, 30),
            'location' => 'Room 101',
            'booked_by' => $student->id,
            'booked_at' => now(),
        ]);

        $body = $this->actingAs($student)->get('/calendar/export')->getContent();

        $this->assertStringContainsString('UID:office-hours-'.$slot->id.'@unigpt', $body);
        $this->assertStringContainsString('LOCATION:Room 101', $body);
    }

    public function test_signed_feed_works_without_a_session(): void
    {
        $student = $this->student();
        $url = URL::signedRoute('calendar.feed', ['user' => $student->id]);

        $response = $this->get($url);

        $response->assertOk();
        $this->assertStringStartsWith('BEGIN:VCALENDAR', $response->getContent());
    }

    public function test_feed_rejects_missing_or_tampered_signatures(): void
    {
        $student = $this->student();

        // No signature at all.
        $this->get("/calendar/feed/{$student->id}")->assertForbidden();

        // Signature minted for one user must not open another's calendar.
        $other = User::where('id', '!=', $student->id)->first();
        $url = URL::signedRoute('calendar.feed', ['user' => $student->id]);
        $tampered = str_replace("/calendar/feed/{$student->id}", "/calendar/feed/{$other->id}", $url);

        $this->get($tampered)->assertForbidden();
    }
}
