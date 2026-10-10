<?php

namespace App\Mail;

use App\Models\Registration;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RegistrationApproved extends Mailable
{
    use SerializesModels;

    public function __construct(public Registration $registration) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Registration Verified — Slot Confirmation',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.registration-approved',
            text: 'emails.registration-approved-text',
        );
    }
}
