<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\User\Models\User;
use App\Mail\AssignmentDueMail;
use App\Mail\WeeklyDigestMail;
use App\Models\Assignment;
use App\Models\FlashcardDeck;
use App\Models\Notification;
use App\Models\Section;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Email digests & deadline nudges: the weekly digest command emails active
 * students who have something to report (honouring the email_digest opt-out),
 * and assignments:remind now sends an email twin of its in-app nudge with the
 * same once-per-student-per-assignment dedupe.
 */
class EmailDigestTest extends TestCase
{
    use DatabaseTransactions;

    private function student(): User
    {
        $student = User::where('email', 'student@university.edu')->first();
        if (! $student || $student->enrolledSectionIds()->isEmpty()) {
            $this->markTestSkipped('Demo student not seeded or has no sections.');
        }

        return $student;
    }

    /**
     * Guarantee the student's digest is non-empty regardless of seed data.
     */
    private function giveDueFlashcard(User $student): void
    {
        FlashcardDeck::create(['user_id' => $student->id, 'title' => 'Digest Deck'])
            ->cards()->create(['front' => 'Q', 'back' => 'A', 'position' => 0, 'last_reviewed_at' => now()]);
    }

    private function makeDueSoonAssignment(User $student): Assignment
    {
        $section = Section::findOrFail($student->enrolledSectionIds()->first());

        return Assignment::create([
            'course_id' => $section->course_id,
            'section_id' => $section->id,
            'title' => 'Nudge Target Assignment',
            'type' => 'homework',
            'status' => 'published',
            'due_at' => now()->addHours(6),
            'total_points' => 10,
        ]);
    }

    private function makeStudent(string $email): User
    {
        $student = User::create([
            'name' => 'Digest Test Student',
            'email' => $email,
            'password' => bcrypt('secret-password'),
            'is_active' => true,
        ]);
        $student->assignRole('student');

        return $student;
    }

    /**
     * One command sweep covers all three digest behaviours (the command scans
     * every student, so it's run once and asserted three ways).
     */
    public function test_weekly_digest_queues_skips_opt_outs_and_empty_digests(): void
    {
        Mail::fake();

        $withContent = $this->student();
        $this->giveDueFlashcard($withContent);

        $optedOut = $this->makeStudent('opted-out-digest@test.local');
        $this->giveDueFlashcard($optedOut);
        $optedOut->update(['preferences' => ['email_digest' => false]]);

        $empty = $this->makeStudent('empty-digest@test.local');

        Artisan::call('digests:send-weekly');

        Mail::assertQueued(WeeklyDigestMail::class, fn (WeeklyDigestMail $mail) => $mail->hasTo($withContent->email)
            && $mail->digest['flashcardsDue'] >= 1);
        Mail::assertNotQueued(WeeklyDigestMail::class, fn (WeeklyDigestMail $mail) => $mail->hasTo($optedOut->email));
        Mail::assertNotQueued(WeeklyDigestMail::class, fn (WeeklyDigestMail $mail) => $mail->hasTo($empty->email));
    }

    public function test_assignment_reminder_sends_email_once_alongside_in_app_nudge(): void
    {
        Mail::fake();
        $student = $this->student();
        $assignment = $this->makeDueSoonAssignment($student);

        Artisan::call('assignments:remind');
        // Second run must not re-send — the in-app dedupe gates the email too.
        Artisan::call('assignments:remind');

        $queued = Mail::queued(AssignmentDueMail::class)
            ->filter(fn (AssignmentDueMail $mail) => $mail->hasTo($student->email)
                && $mail->assignment->id === $assignment->id);
        $this->assertCount(1, $queued, 'Exactly one reminder email per student per assignment.');

        $this->assertTrue(
            Notification::where('user_id', $student->id)
                ->where('data->assignment_id', $assignment->id)
                ->exists(),
            'The in-app reminder is still created.',
        );
    }

    public function test_assignment_reminder_email_respects_opt_out_but_keeps_in_app(): void
    {
        Mail::fake();
        $student = $this->student();
        $student->update(['preferences' => array_merge($student->preferences ?? [], ['email_digest' => false])]);
        $assignment = $this->makeDueSoonAssignment($student);

        Artisan::call('assignments:remind');

        Mail::assertNotQueued(AssignmentDueMail::class, fn (AssignmentDueMail $mail) => $mail->hasTo($student->email));
        $this->assertTrue(
            Notification::where('user_id', $student->id)
                ->where('data->assignment_id', $assignment->id)
                ->exists(),
        );
    }

    public function test_student_can_toggle_the_email_digest_preference(): void
    {
        $student = $this->student();

        $this->actingAs($student)
            ->patch('/settings', [
                'theme' => 'light',
                'notifications' => true,
                'email_digest' => false,
                'language' => 'en',
            ])
            ->assertRedirect();

        $this->assertFalse((bool) ($student->fresh()->preferences['email_digest'] ?? true));
    }
}
