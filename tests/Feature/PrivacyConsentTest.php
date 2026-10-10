<?php

namespace Tests\Feature;

use App\Mail\RegistrationReceived;
use App\Models\Registration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PrivacyConsentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Mail::fake();
        $this->withoutVite();
    }

    public function test_payment_page_shows_unchecked_privacy_notice_controls(): void
    {
        $this->reachPayment();

        $this->get(route('register.payment'))
            ->assertOk()
            ->assertSee('Data Privacy and Participant Consent')
            ->assertSee('Data Privacy Act of 2012 (Republic Act No. 10173)')
            ->assertSee('KONSONTHEGO: Dink After Dark Halloween Open Play Tournament')
            ->assertSee('official documentation, event promotion, information dissemination, and related promotional materials')
            ->assertSee('The personal information I provide may be collected, recorded, stored, and processed')
            ->assertSee('The information I provide may be used to verify my registration details, payment, participant eligibility')
            ->assertSee('Photographs, videos, and other materials captured during the event')
            ->assertSee('The organizers will take reasonable measures to protect the personal information collected')
            ->assertSee('I understand that providing the required information is necessary for my participation')
            ->assertSee('I confirm that all information provided is true and accurate')
            ->assertSee('I have read, understood, and agree to the Data Privacy and Participant Consent terms stated above.')
            ->assertSee('id="privacy_consent"', false)
            ->assertDontSee('id="privacy_consent" checked', false);
    }

    public function test_submit_without_consent_is_rejected_and_does_not_create_a_record_or_send_email(): void
    {
        $this->reachPayment();

        $this->from(route('register.payment'))
            ->post(route('register.submit'), [
                'submission_token' => session('registration_wizard.submission_token'),
                'payment_proof' => $this->fakePng('receipt.png'),
            ])
            ->assertRedirect(route('register.payment'))
            ->assertSessionHasErrors('privacy_consent');

        $this->assertDatabaseCount('registrations', 0);
        Mail::assertNothingSent();

        $this->get(route('register.payment'))
            ->assertOk()
            ->assertSee('Luis')
            ->assertSee('beginner')
            ->assertSee('Please read, understand, and agree to the Data Privacy and Participant Consent terms stated above.');
    }

    public function test_forged_false_consent_is_rejected(): void
    {
        $this->reachPayment();

        $this->from(route('register.payment'))
            ->post(route('register.submit'), [
                'submission_token' => session('registration_wizard.submission_token'),
                'payment_proof' => $this->fakePng('receipt.png'),
                'privacy_consent' => '0',
            ])
            ->assertRedirect(route('register.payment'))
            ->assertSessionHasErrors('privacy_consent');

        $this->assertDatabaseCount('registrations', 0);
        Mail::assertNothingSent();
    }

    public function test_successful_submit_records_server_side_consent_and_sends_application_received_email(): void
    {
        $this->reachPayment();

        $this->post(route('register.submit'), [
            'submission_token' => session('registration_wizard.submission_token'),
            'payment_proof' => $this->fakePng('receipt.png'),
            'privacy_consent' => '1',
            'privacy_consent_at' => '2000-01-01 00:00:00',
            'privacy_notice_version' => 'forged-version',
        ])->assertRedirect(route('register.confirmation'));

        $registration = Registration::query()->first();
        $this->assertNotNull($registration);
        $this->assertTrue($registration->privacy_consent);
        $this->assertNotNull($registration->privacy_consent_at);
        $this->assertNotSame('2000-01-01 00:00:00', $registration->privacy_consent_at->toDateTimeString());
        $this->assertSame('konson_thego_consent_v1', $registration->privacy_notice_version);
        $this->assertSame(config('tournament.privacy_notice_version'), $registration->privacy_notice_version);
        $this->assertNotSame('forged-version', $registration->privacy_notice_version);

        Mail::assertSent(RegistrationReceived::class, fn (RegistrationReceived $mail) => $mail->hasTo($registration->email));
    }

    public function test_existing_applicants_are_not_marked_as_having_consented(): void
    {
        $registration = Registration::factory()->create();

        $this->assertFalse($registration->privacy_consent);
        $this->assertNull($registration->privacy_consent_at);
        $this->assertNull($registration->privacy_notice_version);
    }

    private function reachPayment(): void
    {
        $this->get(route('register.welcome'))->assertOk();
        $this->post(route('register.welcome.continue'))->assertRedirect(route('register.experience'));
        $this->post(route('register.experience.store'), ['has_tournament_experience' => 'no']);
        $this->post(route('register.level.store'), ['entry_level' => 'beginner']);
        $this->post(route('register.personal.store'), [
            'last_name' => 'Reyes',
            'first_name' => 'Luis',
            'contact_number' => '09171234567',
            'address' => 'Malaybalay City, Bukidnon',
            'email' => 'privacy.consent@example.com',
            'photo' => $this->fakePng('player.png'),
        ])->assertRedirect(route('register.payment'));

        $this->get(route('register.payment'))->assertOk();
    }

    private function fakePng(string $name = 'photo.png'): UploadedFile
    {
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');

        return UploadedFile::fake()->createWithContent($name, $png);
    }
}
