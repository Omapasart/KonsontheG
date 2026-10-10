<!DOCTYPE html>
<html>
<body style="margin:0;padding:24px;background:#0d0d0d;color:#fff;font-family:Arial,sans-serif;">
    <div style="max-width:560px;margin:auto;border:1px solid #c8e600;border-radius:16px;padding:24px;background:#161616;">
        <p style="color:#c8e600;letter-spacing:2px;text-transform:uppercase;font-size:12px;">KONSONTHEGO Tournament</p>
        <h1>Category Transfer Request</h1>
        <p>Dear {{ $transfer->registration->fullName() }},</p>
        <p>Thank you for your registration for the KONSONTHEGO Tournament.</p>
        <p>During the review of your registration, our tournament administrators determined that your current skill level may be more appropriate for a higher category.</p>
        <p><strong>Your current category:</strong><br>{{ strtoupper($transfer->current_category->label()) }}</p>
        <p>We would like to ask if you agree to be transferred to:</p>
        <p><strong>{{ strtoupper($transfer->requested_category->label()) }}</strong></p>
        <p>Please review the request and choose whether you agree or decline. </p>
        <p><strong>If you decline this transfer, your tournament registration will be rejected automatically.</strong></p>
        <p style="margin:28px 0;">
            <a href="{{ $transfer->signedShowUrl() }}" style="display:inline-block;background:#c8e600;color:#0d0d0d;text-decoration:none;font-weight:bold;padding:12px 20px;border-radius:999px;margin-right:8px;">AGREE TO TRANSFER</a>
            <a href="{{ $transfer->signedShowUrl() }}" style="display:inline-block;border:1px solid #fff;color:#fff;text-decoration:none;font-weight:bold;padding:12px 20px;border-radius:999px;">DECLINE TRANSFER</a>
        </p>
        <p>Thank you for your understanding and cooperation.</p>
        <p><strong>KONSONTHEGO Tournament Administration</strong></p>
    </div>
</body>
</html>
