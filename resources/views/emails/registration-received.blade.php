<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Application Received</title>
</head>
<body style="margin:0;padding:0;background:#0d0d0d;font-family:Arial,Helvetica,sans-serif;color:#ffffff;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#0d0d0d;padding:24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:560px;background:#161616;border:1px solid #c8e600;border-radius:16px;padding:28px;">
                    <tr>
                        <td>
                            <p style="margin:0 0 8px;color:#c8e600;font-size:12px;letter-spacing:2px;text-transform:uppercase;">KONSONTHEGO Tournament</p>
                            <h1 style="margin:0 0 16px;font-size:24px;color:#ffffff;">Application Received</h1>
                            <p style="margin:0 0 16px;line-height:1.6;color:#e8e8e8;">
                                Hello {{ $registration->fullName() }},
                            </p>
                            <p style="margin:0 0 16px;line-height:1.6;color:#e8e8e8;">
                                Thank you for submitting your application and completing your GCash payment for the KONSONTHEGO Tournament.
                            </p>
                            <h2 style="margin:0 0 12px;font-size:16px;color:#c8e600;">Registration Details</h2>
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin:0 0 16px;border:1px solid #2a2a2a;border-radius:8px;">
                                <tr>
                                    <td style="padding:10px 12px;border-bottom:1px solid #2a2a2a;color:#bdbdbd;font-size:13px;width:46%;">Registration Number</td>
                                    <td style="padding:10px 12px;border-bottom:1px solid #2a2a2a;color:#ffffff;font-size:13px;font-weight:bold;">{{ $registration->registration_number }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:10px 12px;border-bottom:1px solid #2a2a2a;color:#bdbdbd;font-size:13px;">Category</td>
                                    <td style="padding:10px 12px;border-bottom:1px solid #2a2a2a;color:#ffffff;font-size:13px;font-weight:bold;">{{ $registration->entry_level->label() }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:10px 12px;color:#bdbdbd;font-size:13px;">Slot Status</td>
                                    <td style="padding:10px 12px;color:#ffffff;font-size:13px;font-weight:bold;">{{ $registration->slot_status->label() }}</td>
                                </tr>
                            </table>
                            <p style="margin:0 0 16px;line-height:1.6;color:#e8e8e8;">
                                This email confirms that we have received your application. Our team will review and verify the information you provided to ensure that all entries are accurate and complete.
                            </p>
                            <p style="margin:0 0 16px;line-height:1.6;color:#e8e8e8;">
                                Please note that your registration is subject to verification and confirmation. We will notify you once the verification process is complete.
                            </p>
                            <p style="margin:0;line-height:1.6;color:#e8e8e8;">
                                Thank you for your patience and interest in the KONSONTHEGO Tournament!
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
