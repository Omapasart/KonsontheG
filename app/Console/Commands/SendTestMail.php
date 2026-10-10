<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendTestMail extends Command
{
    protected $signature = 'mail:test {email?}';

    protected $description = 'Send a test email using the current mail configuration';

    public function handle(): int
    {
        $email = $this->argument('email') ?: (string) config('mail.from.address');

        if ($email === '') {
            $this->error('No recipient email was provided.');

            return self::FAILURE;
        }

        $this->info('Mailer: '.(string) config('mail.default'));
        $this->info('From: '.(string) config('mail.from.address'));
        $this->info('To: '.$email);

        if (config('mail.default') === 'resend') {
            $this->info('Resend API key: '.(filled(config('services.resend.key')) ? 'set' : 'missing'));
        } else {
            $this->info('Host: '.(string) config('mail.mailers.smtp.host'));
            $this->info('Port: '.(string) config('mail.mailers.smtp.port'));
        }

        try {
            Mail::raw(
                'KONSONTHEGO mail delivery test. If you received this, mail is working.',
                function ($message) use ($email): void {
                    $message->to($email)->subject('KONSONTHEGO mail test');
                }
            );
        } catch (Throwable $exception) {
            $this->error('SEND FAILED: '.$exception->getMessage());

            return self::FAILURE;
        }

        $this->info('SEND OK. Check the inbox and spam folder for "KONSONTHEGO mail test".');

        return self::SUCCESS;
    }
}
