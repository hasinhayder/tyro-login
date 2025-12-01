<?php

namespace HasinHayder\TyroLogin\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OtpMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public string $otp,
        public string $userName,
        public int $expiresInMinutes
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: config('tyro-login.emails.otp.subject', 'Your Verification Code'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'tyro-login::emails.otp',
            with: [
                'otp' => $this->otp,
                'name' => $this->userName,
                'expiresIn' => $this->expiresInMinutes,
                'appName' => config('tyro-login.branding.app_name', config('app.name', 'Laravel')),
            ],
        );
    }
}
