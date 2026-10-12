<?php

namespace App\Mail;

use App\Models\CategoryTransferRequest;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CategoryTransferPaymentNonRefundable extends Mailable
{
    use SerializesModels;

    public function __construct(public CategoryTransferRequest $transfer) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'KONSONTHEGO — Registration Payment Is Not Refundable',
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.category-transfer-payment-non-refundable');
    }
}
