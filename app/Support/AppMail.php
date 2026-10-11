<?php

namespace App\Support;

use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class AppMail
{
    public static function send(string $email, Mailable $mailable): bool
    {
        try {
            Mail::mailer(self::mailer())->to($email)->send($mailable);

            return true;
        } catch (Throwable $exception) {
            Log::error('Failed to send email.', [
                'mailable' => $mailable::class,
                'message' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    public static function mailer(): string
    {
        $configured = (string) config('mail.default');

        if (in_array($configured, ['array', 'log'], true)) {
            return $configured;
        }

        return 'brevo';
    }
}
