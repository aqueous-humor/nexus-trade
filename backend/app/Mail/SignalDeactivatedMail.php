<?php

namespace App\Mail;

use App\Models\Signal;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SignalDeactivatedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;
    use \App\Mail\Concerns\LogsMailFailure;

    public int $tries = 3;
    public array $backoff = [30, 300, 1800];

    public function __construct(public readonly Signal $signal) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Signal Deactivated — ' . $this->signal->name);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.signal-deactivated');
    }
}
