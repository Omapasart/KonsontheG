<?php

namespace Tests\Feature;

use App\Mail\RegistrationReceived;
use App\Models\Registration;
use App\Support\AppMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class BrevoMailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'mail.default' => 'brevo',
            'mail.from.address' => 'from@example.com',
            'mail.from.name' => 'KONSONTHEGO',
            'mail.mailers.brevo.key' => 'test-brevo-key',
            'services.brevo.key' => 'test-brevo-key',
        ]);

        Mail::purge('brevo');
    }

    public function test_registration_email_is_posted_to_brevo_api(): void
    {
        Http::fake([
            'https://api.brevo.com/v3/smtp/email' => Http::response(['messageId' => 'abc-123'], 201),
        ]);

        $registration = Registration::factory()->create([
            'email' => 'applicant@example.com',
        ]);

        $this->assertTrue(AppMail::send($registration->email, new RegistrationReceived($registration)));

        Http::assertSent(function ($request) use ($registration) {
            return $request->url() === 'https://api.brevo.com/v3/smtp/email'
                && $request->hasHeader('api-key', 'test-brevo-key')
                && $request['sender']['email'] === 'from@example.com'
                && $request['to'][0]['email'] === $registration->email
                && $request['subject'] === 'Application Received'
                && filled($request['htmlContent']);
        });
    }

    public function test_brevo_api_errors_are_reported_without_sending(): void
    {
        Http::fake([
            'https://api.brevo.com/v3/smtp/email' => Http::response(['message' => 'Key not found'], 401),
        ]);

        $registration = Registration::factory()->create();

        $this->assertFalse(AppMail::send($registration->email, new RegistrationReceived($registration)));
    }
}
