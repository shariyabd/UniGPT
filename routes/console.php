<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Remind students each morning about assignments due within the next 24 hours
// (in-app notification + email, once per student per assignment).
Schedule::command('assignments:remind')->dailyAt('08:00');

// Weekly email digest: deadlines, fresh grades, booked office hours, due flashcards.
Schedule::command('digests:send-weekly')->weeklyOn(1, '07:00');

// Delete exam recordings/snapshots of finalised attempts past the retention window.
Schedule::command('exam:prune-evidence')->weeklyOn(0, '03:00');
