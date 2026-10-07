<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>You are registered — KONSONTHEGO</title>
</head>
<body style="margin:0;padding:0;background:#0d0d0d;font-family:Arial,Helvetica,sans-serif;color:#ffffff;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#0d0d0d;padding:24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:560px;background:#161616;border:1px solid #c8e600;border-radius:16px;padding:28px;">
                    <tr>
                        <td>
                            <p style="margin:0 0 8px;color:#c8e600;font-size:12px;letter-spacing:2px;text-transform:uppercase;">KONSONTHEGO Tournament</p>
                            <h1 style="margin:0 0 16px;font-size:24px;color:#ffffff;">You Are Registered</h1>
                            <p style="margin:0 0 16px;line-height:1.6;color:#e8e8e8;">
                                Hello {{ $registration->fullName() }},
                            </p>
                            <p style="margin:0 0 16px;line-height:1.6;color:#e8e8e8;">
                                Thank you for completing your GCash payment and registration. This email confirms that you are now registered for the KONSONTHEGO Tournament.
                            </p>
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#0d0d0d;border-radius:12px;margin:0 0 16px;">
                                <tr>
                                    <td style="padding:16px;">
                                        <p style="margin:0 0 8px;color:#c8e600;"><strong>Registration No.:</strong> {{ $registration->registration_number }}</p>
                                        <p style="margin:0 0 8px;color:#e8e8e8;"><strong>Participant:</strong> {{ $registration->fullName() }}</p>
                                        <p style="margin:0 0 8px;color:#e8e8e8;"><strong>Email:</strong> {{ $registration->email }}</p>
                                        <p style="margin:0 0 8px;color:#e8e8e8;"><strong>Entry level:</strong> {{ $registration->entry_level->label() }}</p>
                                        <p style="margin:0;color:#e8e8e8;"><strong>Tournament experience:</strong> {{ $registration->has_tournament_experience->label() }}</p>
                                    </td>
                                </tr>
                            </table>
                            <p style="margin:0 0 16px;line-height:1.6;color:#e8e8e8;">
                                Your registration and GCash payment proof will be reviewed. Please allow up to <strong>{{ config('tournament.confirmation_hours', 28) }} hours</strong> for verification.
                            </p>
                            <p style="margin:0 0 16px;line-height:1.6;color:#e8e8e8;">
                                Keep this email for your records. Please also check your spam/junk folder for further tournament updates.
                            </p>
                            <p style="margin:0;color:#9a9a9a;font-size:12px;">
                                This message confirms that your registration was submitted. Do not reply with files or payment details.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
