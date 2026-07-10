<?php

declare(strict_types=1);

namespace App\Mail;

use App\Domain\User\Models\User;
use App\Models\Assignment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Email twin of the in-app "assignment due soon" reminder — sent once per
 * student per assignment by the assignments:remind command, alongside the
 * in-app notification (whose dedupe gates both).
 */
class AssignmentDueMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly User $student,
        public readonly Assignment $assignment,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Due soon: {$this->assignment->title}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.assignment-due',
            with: [
                'student' => $this->student,
                'assignment' => $this->assignment,
            ],
        );
    }
}
