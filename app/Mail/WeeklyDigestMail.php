<?php

declare(strict_types=1);

namespace App\Mail;

use App\Domain\User\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * The student's weekly academic digest: upcoming deadlines, freshly posted
 * grades, due flashcards and booked office hours. Built by EmailDigestService
 * (which skips students with nothing to report or with digests opted out).
 */
class WeeklyDigestMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * @param  array<string, mixed>  $digest
     */
    public function __construct(
        public readonly User $student,
        public readonly array $digest,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your week ahead at '.config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.weekly-digest',
            with: [
                'student' => $this->student,
                'digest' => $this->digest,
            ],
        );
    }
}
