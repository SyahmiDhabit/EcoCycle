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

    public function __construct(public User $business, public string $decision) {}

    public function envelope(): Envelope
    {
        $subject = match ($this->decision) {
            'approved' => 'Update regarding your EcoCycle business registration: Application Approved',
            'rejected' => 'Update regarding your EcoCycle business registration: Application Rejected',
            default => 'Update regarding your EcoCycle business registration: Changes Requested',
        };

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.business-application-decision',
            text: 'emails.business-application-decision-text',
            with: [
                'business' => $this->business,
                'decision' => $this->decision,
            ],
        );
    }
}
