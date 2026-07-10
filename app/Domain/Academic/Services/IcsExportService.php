<?php

declare(strict_types=1);

namespace App\Domain\Academic\Services;

use App\Domain\User\Models\User;
use Illuminate\Support\Carbon;

/**
 * Serialises a student's unified calendar (assignment deadlines, exams,
 * tasks, booked office hours) into an iCalendar (RFC 5545) document, so it
 * can be downloaded or subscribed to from Google Calendar / Outlook / Apple
 * Calendar via the signed feed URL.
 */
class IcsExportService
{
    public function __construct(private readonly CalendarService $calendar) {}

    public function forStudent(User $student): string
    {
        $events = $this->calendar->build($student)['events'];

        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//UniNexus//Academic Calendar//EN',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'X-WR-CALNAME:UniNexus — '.$this->escape($student->name),
        ];

        foreach ($events as $event) {
            $lines = array_merge($lines, $this->vevent($event));
        }

        $lines[] = 'END:VCALENDAR';

        // RFC 5545: lines end with CRLF and must be folded at 75 octets.
        return implode("\r\n", array_map($this->fold(...), $lines))."\r\n";
    }

    /**
     * @param  array<string, mixed>  $event
     * @return array<int, string>
     */
    private function vevent(array $event): array
    {
        $start = Carbon::parse($event['date'].' '.($event['time'] ?? '09:00'));

        $summary = match ($event['type']) {
            'assignment' => 'Due: '.$event['title'],
            'exam' => 'Exam: '.$event['title'],
            'task' => 'Task: '.$event['title'],
            default => $event['title'],
        };

        $description = collect([
            $event['course'] ?? null ? 'Course: '.$event['course'] : null,
            $event['description'] ?? null,
        ])->filter()->implode('\n');

        $lines = [
            'BEGIN:VEVENT',
            'UID:'.$event['id'].'@uninexus',
            'DTSTAMP:'.now()->utc()->format('Ymd\THis\Z'),
            'SUMMARY:'.$this->escape($summary),
        ];

        if (empty($event['time'])) {
            // Untimed items (tasks and any event without a clock time) export
            // as all-day events.
            $lines[] = 'DTSTART;VALUE=DATE:'.$start->format('Ymd');
        } else {
            $lines[] = 'DTSTART:'.$start->utc()->format('Ymd\THis\Z');
            $lines[] = 'DTEND:'.$start->copy()->addHour()->utc()->format('Ymd\THis\Z');
        }

        if ($description !== '') {
            $lines[] = 'DESCRIPTION:'.$this->escape($description);
        }

        if (! empty($event['location'])) {
            $lines[] = 'LOCATION:'.$this->escape($event['location']);
        }

        $lines[] = 'END:VEVENT';

        return $lines;
    }

    /**
     * Escape iCalendar text values (RFC 5545 §3.3.11). Literal "\n" sequences
     * produced by joins above are preserved as encoded newlines.
     */
    private function escape(string $value): string
    {
        return str_replace(
            ['\\', ';', ',', "\n"],
            ['\\\\', '\;', '\,', '\n'],
            $value,
        );
    }

    /**
     * Fold a content line at 75 octets with a leading space on continuations.
     */
    private function fold(string $line): string
    {
        if (strlen($line) <= 75) {
            return $line;
        }

        $folded = substr($line, 0, 75);
        $rest = substr($line, 75);
        while (strlen($rest) > 74) {
            $folded .= "\r\n ".substr($rest, 0, 74);
            $rest = substr($rest, 74);
        }

        return $folded.($rest !== '' ? "\r\n ".$rest : '');
    }
}
