<!DOCTYPE html>
<html>
<body style="margin:0;padding:24px;background:#0d0d0d;color:#fff;font-family:Arial,sans-serif;">
    <div style="max-width:560px;margin:auto;border:1px solid #c8e600;border-radius:16px;padding:24px;background:#161616;">
        <p style="color:#c8e600;letter-spacing:2px;text-transform:uppercase;font-size:12px;">KONSONTHEGO Tournament</p>
        <h1>Category Transfer Could Not Be Completed</h1>
        <p>The applicant {{ $transfer->registration->fullName() }} ({{ $transfer->registration->registration_number }}) agreed to transfer from {{ $transfer->current_category->label() }} to {{ $transfer->requested_category->label() }}, but the requested category is currently full, including its waiting list.</p>
        <p>The applicant's category was not changed. The transfer request remains pending until capacity is available or a new request is issued.</p>
    </div>
</body>
</html>
