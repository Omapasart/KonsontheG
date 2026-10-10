<!DOCTYPE html>
<html>
<body style="margin:0;padding:24px;background:#0d0d0d;color:#fff;font-family:Arial,sans-serif;">
    <div style="max-width:560px;margin:auto;border:1px solid #c8e600;border-radius:16px;padding:24px;background:#161616;">
        <p style="color:#c8e600;letter-spacing:2px;text-transform:uppercase;font-size:12px;">KONSONTHEGO Tournament</p>
        <h1>Registration Rejected</h1>
        <p>Dear {{ $transfer->registration->fullName() }},</p>
        <p>Your request to decline the proposed category transfer has been recorded. Since you did not agree to compete in the proposed category, your tournament registration has been rejected.</p>
        <p><strong>Registration No.:</strong> {{ $transfer->registration->registration_number }}<br>
        <strong>Registered Category:</strong> {{ $transfer->current_category->label() }}<br>
        <strong>Proposed Category:</strong> {{ $transfer->requested_category->label() }}<br>
        <strong>Registration Status:</strong> Rejected</p>
        <p>If you need clarification, please contact the tournament organizers.</p>
        <p><strong>KONSONTHEGO Tournament Administration</strong></p>
    </div>
</body>
</html>
