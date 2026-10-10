<!DOCTYPE html>
<html>
<body style="margin:0;padding:24px;background:#0d0d0d;color:#fff;font-family:Arial,sans-serif;">
    <div style="max-width:560px;margin:auto;border:1px solid #c8e600;border-radius:16px;padding:24px;background:#161616;">
        <p style="color:#c8e600;letter-spacing:2px;text-transform:uppercase;font-size:12px;">KONSONTHEGO Tournament</p>
        <h1>Category Transfer Confirmed</h1>
        <p>Dear {{ $registration->fullName() }},</p>
        <p>Your category transfer request has been successfully processed.</p>
        <p><strong>Previous Category:</strong><br>{{ strtoupper($transfer->current_category->label()) }}</p>
        <p><strong>New Category:</strong><br>{{ strtoupper($transfer->requested_category->label()) }}</p>
        @if ($registration->slot_status->value === 'waiting')
            <p><strong>Slot Status:</strong><br>WAITING LIST #{{ $registration->waiting_list_position }}</p>
        @else
            <p><strong>Slot Status:</strong><br>CONFIRMED</p>
        @endif
        <p>Thank you for your cooperation.</p>
        <p><strong>KONSONTHEGO Tournament Administration</strong></p>
    </div>
</body>
</html>
