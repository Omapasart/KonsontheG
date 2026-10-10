<!DOCTYPE html>
<html>
<body style="margin:0;padding:24px;background:#0d0d0d;color:#fff;font-family:Arial,sans-serif;">
    <div style="max-width:560px;margin:auto;border:1px solid #c8e600;border-radius:16px;padding:24px;background:#161616;">
        <p style="color:#c8e600;letter-spacing:2px;text-transform:uppercase;font-size:12px;">KONSONTHEGO Tournament</p>
        <h1>Congratulations!</h1>
        <p>Hello {{ $registration->fullName() }},</p>
        <p>A tournament slot has become available in the {{ $registration->entry_level->label() }} category, and you have been promoted from the waiting list to a confirmed tournament slot.</p>
        <p><strong>Registration No.:</strong> {{ $registration->registration_number }}<br>
        <strong>Category:</strong> {{ $registration->entry_level->label() }}</p>
        <p>Your tournament slot is now CONFIRMED.</p>
    </div>
</body>
</html>
