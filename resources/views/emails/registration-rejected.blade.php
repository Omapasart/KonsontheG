<!DOCTYPE html>
<html>
<body style="margin:0;padding:24px;background:#0d0d0d;color:#fff;font-family:Arial,sans-serif;">
    <div style="max-width:560px;margin:auto;border:1px solid #c8e600;border-radius:16px;padding:24px;background:#161616;">
        <p style="color:#c8e600;letter-spacing:2px;text-transform:uppercase;font-size:12px;">KONSONTHEGO Tournament</p>
        <h1>Registration Update</h1>
        <p>Hello {{ $registration->fullName() }},</p>
        <p>Your KONSONTHEGO Tournament registration <strong>{{ $registration->registration_number }}</strong> was not approved.</p>
        <p><strong>Reason:</strong> {{ $registration->rejection_reason }}</p>
        <p>If you believe this can be corrected, please contact the tournament organizers with your registration number.</p>
    </div>
</body>
</html>
