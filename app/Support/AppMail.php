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
            Mail::mailer(config('mail.default'))->to($email)->send($mailable);

            return true;
        } catch (Throwable $exception) {
            Log::error('Failed to send email.', [
                'mailable' => $mailable::class,
                'message' => $exception->getMessage(),
            ]);

            return false;
        }
    }
}
