<!DOCTYPE html>
<html>
<body style="margin:0;padding:24px;background:#0d0d0d;color:#fff;font-family:Arial,sans-serif;">
    <div style="max-width:560px;margin:auto;border:1px solid #c8e600;border-radius:16px;padding:24px;background:#161616;">
        <p style="color:#c8e600;letter-spacing:2px;text-transform:uppercase;font-size:12px;">KONSONTHEGO Tournament</p>
        <h1>Payment Verified</h1>
        <p>Hello {{ $registration->fullName() }},</p>
        <p>Your GCash payment for registration <strong>{{ $registration->registration_number }}</strong> has been verified.</p>
        <h2 style="margin:20px 0 12px;font-size:16px;color:#c8e600;">Registration Details</h2>
        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin:0 0 16px;border:1px solid #2a2a2a;border-radius:8px;">
            <tr>
                <td style="padding:10px 12px;border-bottom:1px solid #2a2a2a;color:#bdbdbd;font-size:13px;width:46%;">Applicant Name</td>
                <td style="padding:10px 12px;border-bottom:1px solid #2a2a2a;color:#ffffff;font-size:13px;font-weight:bold;">{{ $registration->fullName() }}</td>
            </tr>
            <tr>
                <td style="padding:10px 12px;border-bottom:1px solid #2a2a2a;color:#bdbdbd;font-size:13px;">Registration Number</td>
                <td style="padding:10px 12px;border-bottom:1px solid #2a2a2a;color:#ffffff;font-size:13px;font-weight:bold;">{{ $registration->registration_number }}</td>
            </tr>
            <tr>
                <td style="padding:10px 12px;border-bottom:1px solid #2a2a2a;color:#bdbdbd;font-size:13px;">Category</td>
                <td style="padding:10px 12px;border-bottom:1px solid #2a2a2a;color:#ffffff;font-size:13px;font-weight:bold;">{{ $registration->entry_level->label() }}</td>
            </tr>
            <tr>
                <td style="padding:10px 12px;border-bottom:1px solid #2a2a2a;color:#bdbdbd;font-size:13px;">Payment Status</td>
                <td style="padding:10px 12px;border-bottom:1px solid #2a2a2a;color:#ffffff;font-size:13px;font-weight:bold;">{{ $registration->payment_status->label() }}</td>
            </tr>
            <tr>
                <td style="padding:10px 12px;color:#bdbdbd;font-size:13px;">Slot Status</td>
                <td style="padding:10px 12px;color:#ffffff;font-size:13px;font-weight:bold;">{{ $registration->slot_status->label() }}</td>
            </tr>
        </table>
        <p>Thank you. Watch your email for further registration updates.</p>
    </div>
</body>
</html>
