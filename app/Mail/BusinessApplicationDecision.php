<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BusinessApplicationDecision extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public User $business, public string $decision)
    {
    }

    public function envelope(): Envelope
    {
        $subject = $this->decision === 'approved'
            ? 'Your EcoCycle business application is approved'
            : 'Update requested for your EcoCycle business application';

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.business-application-decision',
            with: ['business' => $this->business, 'decision' => $this->decision],
        );
    }
}
