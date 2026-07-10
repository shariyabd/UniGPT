<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Notification\Services\EmailDigestService;
use App\Domain\Notification\Services\NotificationService;
use App\Enums\NotificationType;
use App\Mail\AssignmentDueMail;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Notification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendAssignmentReminders extends Command
{
    protected $signature = 'assignments:remind {--hours=24 : How many hours ahead to look for due assignments}';

    protected $description = 'Remind students of published assignments due soon that they have not yet submitted.';

    private const REMINDER_TITLE = 'Assignment due soon';

    public function handle(NotificationService $notifications, EmailDigestService $digests): int
    {
        $hours = (int) $this->option('hours');
        $now = now();
        $window = $now->copy()->addHours($hours);

        $assignments = Assignment::where('status', 'published')
            ->whereNotNull('due_at')
            ->whereBetween('due_at', [$now, $window])
            ->with('course')
            ->get();

        $sent = 0;

        foreach ($assignments as $assignment) {
            foreach ($assignment->course->students()->get() as $student) {
                $alreadySubmitted = AssignmentSubmission::where('assignment_id', $assignment->id)
                    ->where('user_id', $student->id)
                    ->exists();

                if ($alreadySubmitted) {
                    continue;
                }

                // Send at most one reminder per student per assignment.
                $alreadyReminded = Notification::where('user_id', $student->id)
                    ->where('title', self::REMINDER_TITLE)
                    ->where('data->assignment_id', $assignment->id)
                    ->exists();

                if ($alreadyReminded) {
                    continue;
                }

                $notifications->notify(
                    user: $student,
                    type: NotificationType::ASSIGNMENT,
                    title: self::REMINDER_TITLE,
                    message: "\"{$assignment->title}\" in {$assignment->course->code} is due {$assignment->due_at->format('M j, g:i A')}.",
                    link: route('assignments.show', $assignment->id),
                    data: ['assignment_id' => $assignment->id, 'course_id' => $assignment->course_id, 'reminder' => true],
                );

                // Email twin of the in-app nudge — the dedupe above already
                // guarantees at most one per student per assignment.
                if ($digests->wantsEmails($student)) {
                    Mail::to($student->email)->queue(new AssignmentDueMail($student, $assignment));
                }

                $sent++;
            }
        }

        $this->info("Sent {$sent} assignment reminder(s).");

        return self::SUCCESS;
    }
}
