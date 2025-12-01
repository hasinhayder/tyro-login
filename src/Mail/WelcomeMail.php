<?php

namespace HasinHayder\TyroLogin\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public string $userName,
        public ?string $loginUrl = null
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: config('tyro-login.emails.welcome.subject', 'Welcome to ' . config('tyro-login.branding.app_name', config('app.name', 'Laravel'))),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'tyro-login::emails.welcome',
            with: [
                'name' => $this->userName,
                'loginUrl' => $this->loginUrl ?? url(config('tyro-login.routes.prefix', '') . '/login'),
                'appName' => config('tyro-login.branding.app_name', config('app.name', 'Laravel')),
            ],
        );
    }
}
