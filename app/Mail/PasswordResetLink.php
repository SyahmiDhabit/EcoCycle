<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PasswordResetLink extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $recipientName,
        public string $resetUrl,
        public string $language,
    ) {
    }

    public function envelope(): Envelope
    {
        $subject = $this->language === 'ms'
            ? 'Pautan tetapan semula kata laluan EcoCycle'
            : 'EcoCycle password reset link';

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.password-reset-link',
            with: [
                'recipientName' => $this->recipientName,
                'resetUrl' => $this->resetUrl,
                'language' => $this->language,
            ],
        );
    }
}
