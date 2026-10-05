<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TransactionalEmail extends Mailable
{
    use Queueable,SerializesModels;

    public function __construct(public string $mailSubject, public string $htmlBody, public string $textBody, public ?string $replyToAddress = null) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->mailSubject, replyTo: $this->replyToAddress ? [new Address($this->replyToAddress)] : []);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.transactional', text: 'emails.transactional-text', with: ['body' => $this->htmlBody, 'textBody' => $this->textBody]);
    }
}
