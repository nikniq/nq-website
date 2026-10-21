<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

/**
 * The sign-up link. Signed and time-limited; opening it marks the email
 * verified, logs the player in and sends them to choose a password.
 */
class VerifyEmail extends Mailable
{
    use Queueable, SerializesModels;

    public string $url;

    public function __construct(public User $user)
    {
        $this->url = URL::temporarySignedRoute(
            'register.verify',
            now()->addMinutes((int) config('portal.verify_ttl_minutes', 60)),
            ['user' => $user->id, 'hash' => sha1($user->email)],
        );
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Confirm your email for '.config('portal.name'));
    }

    public function content(): Content
    {
        return new Content(view: 'mail.verify-email');
    }
}
