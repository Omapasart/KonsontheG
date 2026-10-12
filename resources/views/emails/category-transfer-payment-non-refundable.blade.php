<!DOCTYPE html>
<html>
<body style="margin:0;padding:24px;background:#0d0d0d;color:#fff;font-family:Arial,sans-serif;">
    <div style="max-width:560px;margin:auto;border:1px solid #c8e600;border-radius:16px;padding:24px;background:#161616;">
        <p style="color:#c8e600;letter-spacing:2px;text-transform:uppercase;font-size:12px;">KONSONTHEGO Tournament</p>
        <h1>Payment Notice</h1>
        <p>Dear {{ $transfer->registration->fullName() }},</p>
        <p>Your GCash payment for registration <strong>{{ $transfer->registration->registration_number }}</strong> was already verified. After you declined the proposed category transfer, your registration and slot were rejected.</p>
        <p><strong>Tournament registration payments are not refundable.</strong></p>
        <p>It is your obligation to find a proxy who can take over this registration if you will not compete.</p>
        <p><strong>Registration No.:</strong> {{ $transfer->registration->registration_number }}<br>
        <strong>Payment Status:</strong> {{ $transfer->registration->payment_status->label() }}<br>
        <strong>Slot Status:</strong> {{ $transfer->registration->slot_status->label() }}</p>
        <p>If you need clarification, please contact the tournament organizers.</p>
        <p><strong>KONSONTHEGO Tournament Administration</strong></p>
    </div>
</body>
</html>
