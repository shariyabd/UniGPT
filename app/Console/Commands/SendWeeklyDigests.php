<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Notification\Services\EmailDigestService;
use App\Domain\User\Models\User;
use App\Mail\WeeklyDigestMail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendWeeklyDigests extends Command
{
    protected $signature = 'digests:send-weekly';

    protected $description = 'Email each active student their weekly digest (deadlines, grades, office hours, due flashcards).';

    public function handle(EmailDigestService $digests): int
    {
        $sent = 0;
        $skipped = 0;

        User::query()
            ->where('is_active', true)
            ->whereHas('roles', fn ($query) => $query->where('slug', 'student'))
            ->chunkById(200, function ($students) use ($digests, &$sent, &$skipped) {
                foreach ($students as $student) {
                    if (! $digests->wantsEmails($student)) {
                        $skipped++;

                        continue;
                    }

                    $digest = $digests->digestFor($student);
                    if ($digest === null) {
                        $skipped++;

                        continue;
                    }

                    Mail::to($student->email)->queue(new WeeklyDigestMail($student, $digest));
                    $sent++;
                }
            });

        $this->info("Queued {$sent} weekly digest(s); skipped {$skipped} (opted out or nothing to report).");

        return self::SUCCESS;
    }
}
