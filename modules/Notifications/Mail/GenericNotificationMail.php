<?php

declare(strict_types=1);

namespace Modules\Notifications\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Reusable transactional email, sent through the configured mailer (Resend).
 * Implements ShouldQueue so it is ALWAYS sent off the request thread (backend.md §3).
 * Subject + body lines are passed in (content, not hardcoded UI strings — §10).
 */
final class GenericNotificationMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $backoff = 10;

    /** @param list<string> $lines */
    public function __construct(
        public readonly string $subjectLine,
        public readonly array $lines = [],
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->subjectLine);
    }

    public function content(): Content
    {
        return new Content(markdown: 'notifications::emails.generic');
    }
}
