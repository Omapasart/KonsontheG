<!DOCTYPE html>
<html>
<body style="margin:0;padding:24px;background:#0d0d0d;color:#fff;font-family:Arial,sans-serif;">
    <div style="max-width:560px;margin:auto;border:1px solid #c8e600;border-radius:16px;padding:24px;background:#161616;">
        <p style="color:#c8e600;letter-spacing:2px;text-transform:uppercase;font-size:12px;">KONSONTHEGO Tournament</p>
        <h1>Transferred to a Regular Slot</h1>
        <p>Hello {{ $registration->fullName() }},</p>
        <p>You have been transferred from the waiting list to a regular slot in the {{ $registration->entry_level->label() }} category.</p>
        <p><strong>Registration No.:</strong> {{ $registration->registration_number }}<br>
        <strong>Category:</strong> {{ $registration->entry_level->label() }}<br>
        <strong>Slot Status:</strong> {{ $registration->slot_status->label() }}<br>
        <strong>Payment Status:</strong> {{ $registration->payment_status->label() }}</p>
        <p>This is not yet a confirmed tournament slot. An administrator still needs to verify your payment and application. We will email you again once your slot is confirmed.</p>
    </div>
</body>
</html>
