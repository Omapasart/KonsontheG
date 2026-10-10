<?php

namespace App\Mail;

use App\Models\CategoryTransferRequest;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CategoryTransferRequested extends Mailable
{
    use SerializesModels;

    public function __construct(public CategoryTransferRequest $transfer) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'KONSONTHEGO — Category Transfer Request',
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.category-transfer-requested');
    }
}
