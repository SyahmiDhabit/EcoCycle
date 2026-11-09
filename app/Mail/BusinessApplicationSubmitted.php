<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BusinessApplicationSubmitted extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public User $business, public bool $isUpdate = false)
    {
    }

    public function envelope(): Envelope
    {
        $subject = ($this->isUpdate ? 'Updated EcoCycle business application: ' : 'New EcoCycle business application: ').$this->business->business_name;

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.business-application-submitted',
            with: ['business' => $this->business, 'isUpdate' => $this->isUpdate],
        );
    }
}
