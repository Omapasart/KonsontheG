<?php

namespace App\Mail;

use App\Models\CategoryTransferRequest;
use App\Models\Registration;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CategoryTransferConfirmed extends Mailable
{
    use SerializesModels;

    public function __construct(
        public Registration $registration,
        public CategoryTransferRequest $transfer,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'KONSONTHEGO — Category Transfer Confirmed',
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.category-transfer-confirmed');
    }
}
