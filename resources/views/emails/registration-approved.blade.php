<!DOCTYPE html>
<html>
<body style="margin:0;padding:24px;background:#0d0d0d;color:#fff;font-family:Arial,sans-serif;">
    <div style="max-width:560px;margin:auto;border:1px solid #c8e600;border-radius:16px;padding:24px;background:#161616;">
        <p style="color:#c8e600;letter-spacing:2px;text-transform:uppercase;font-size:12px;">KONSONTHEGO Tournament</p>
        <h1>Registration Approved</h1>
        <p>Hello {{ $registration->fullName() }},</p>
        <p>Your KONSONTHEGO Tournament registration has been <strong>approved</strong>.</p>
        <p><strong>Registration No.:</strong> {{ $registration->registration_number }}<br>
        <strong>Entry level:</strong> {{ $registration->entry_level->label() }}</p>
        <p>Please keep this email for your records and watch for further tournament instructions.</p>
    </div>
</body>
</html>
