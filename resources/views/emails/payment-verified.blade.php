<!DOCTYPE html>
<html>
<body style="margin:0;padding:24px;background:#0d0d0d;color:#fff;font-family:Arial,sans-serif;">
    <div style="max-width:560px;margin:auto;border:1px solid #c8e600;border-radius:16px;padding:24px;background:#161616;">
        <p style="color:#c8e600;letter-spacing:2px;text-transform:uppercase;font-size:12px;">KONSONTHEGO Tournament</p>
        <h1>Payment Verified</h1>
        <p>Hello {{ $registration->fullName() }},</p>
        <p>Your GCash payment for registration <strong>{{ $registration->registration_number }}</strong> has been verified.</p>
        <p>Thank you. Watch your email for further registration updates.</p>
    </div>
</body>
</html>
