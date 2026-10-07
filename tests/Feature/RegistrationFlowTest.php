<?php

namespace Tests\Feature;

use App\Mail\RegistrationReceived;
use App\Models\Registration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RegistrationFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Mail::fake();
        $this->withoutVite();
    }

    public function test_welcome_page_is_shown(): void
    {
        $this->get(route('register.welcome'))
            ->assertOk()
            ->assertSee('Tournament Registration')
            ->assertSee("Let's Go", false);
    }

    public function test_steps_cannot_be_skipped(): void
    {
        $this->get(route('register.experience'))->assertRedirect(route('register.welcome'));
        $this->get(route('register.level'))->assertRedirect(route('register.welcome'));
        $this->get(route('register.personal'))->assertRedirect(route('register.welcome'));
        $this->get(route('register.payment'))->assertRedirect(route('register.welcome'));
        $this->get(route('register.confirmation'))->assertRedirect(route('register.welcome'));
    }

    public function test_experience_requires_a_choice(): void
    {
        $this->startWizard();

        $this->from(route('register.experience'))
            ->post(route('register.experience.store'), [])
            ->assertSessionHasErrors('has_tournament_experience')
            ->assertRedirect(route('register.experience'));
    }

    public function test_personal_data_validates_email_phone_and_photo(): void
    {
        $this->startWizard();
        $this->post(route('register.experience.store'), ['has_tournament_experience' => 'yes']);
        $this->post(route('register.level.store'), ['entry_level' => 'novice']);

        $this->from(route('register.personal'))
            ->post(route('register.personal.store'), [
                'last_name' => 'Santos',
                'first_name' => 'Ana',
                'contact_number' => '12345',
                'address' => 'Malaybalay City',
                'email' => 'not-an-email',
                'photo' => UploadedFile::fake()->create('hack.php', 20, 'text/x-php'),
            ])
            ->assertSessionHasErrors(['contact_number', 'email', 'photo'])
            ->assertRedirect(route('register.personal'));
    }

    public function test_complete_registration_flow_stores_record_and_queues_email(): void
    {
        $this->startWizard();

        $this->post(route('register.experience.store'), ['has_tournament_experience' => 'no'])
            ->assertRedirect(route('register.level'));

        $this->post(route('register.level.store'), ['entry_level' => 'beginner'])
            ->assertRedirect(route('register.personal'));

        $this->post(route('register.personal.store'), [
            'last_name' => 'Reyes',
            'first_name' => 'Luis',
            'middle_initial' => 'A',
            'contact_number' => '09171234567',
            'address' => 'Malaybalay City, Bukidnon',
            'facebook' => 'luis.reyes',
            'email' => 'Luis.Reyes@example.com',
            'photo' => $this->fakePng('player.png'),
        ])->assertRedirect(route('register.payment'));

        $this->get(route('register.payment'))
            ->assertOk()
            ->assertSee('Luis')
            ->assertSee('beginner')
            ->assertSee('Proof of Payment');

        $token = session('registration_wizard.submission_token');
        $this->assertNotEmpty($token);

        $this->post(route('register.submit'), [
            'submission_token' => $token,
            'payment_proof' => $this->fakePng('receipt.png'),
        ])->assertRedirect(route('register.confirmation'));

        $this->get(route('register.confirmation'))
            ->assertOk()
            ->assertSee('Thank You for Your Interest in KONSONTHEGO')
            ->assertSee('luis.reyes@example.com')
            ->assertSee('KTG-')
            ->assertDontSee(config('tournament.gcash_number'));

        $this->assertDatabaseCount('registrations', 1);

        $registration = Registration::query()->first();
        $this->assertSame('KTG-'.now()->format('Y').'-0001', $registration->registration_number);
        $this->assertSame('no', $registration->has_tournament_experience->value);
        $this->assertSame('beginner', $registration->entry_level->value);
        $this->assertSame('luis.reyes@example.com', $registration->email);
        $this->assertSame('pending', $registration->payment_status->value);
        $this->assertSame('pending', $registration->registration_status->value);
        $this->assertTrue(Storage::disk('local')->exists($registration->photo_path));
        $this->assertTrue(Storage::disk('local')->exists($registration->payment_proof_path));
        $this->assertStringStartsWith('registrations/photos/', $registration->photo_path);
        $this->assertStringStartsWith('registrations/proofs/', $registration->payment_proof_path);

        Mail::assertSent(RegistrationReceived::class, function (RegistrationReceived $mail) use ($registration) {
            return $mail->registration->is($registration)
                && $mail->hasTo('luis.reyes@example.com')
                && $mail->hasSubject('You are registered — KONSONTHEGO '.$registration->registration_number);
        });
    }

    public function test_duplicate_submission_token_does_not_create_a_second_record(): void
    {
        $this->startWizard();
        $this->post(route('register.experience.store'), ['has_tournament_experience' => 'yes']);
        $this->post(route('register.level.store'), ['entry_level' => 'intermediate']);
        $this->post(route('register.personal.store'), [
            'last_name' => 'Cruz',
            'first_name' => 'Maria',
            'contact_number' => '+639171234567',
            'address' => 'Valencia City',
            'email' => 'maria@example.com',
            'photo' => $this->fakePng('maria.png'),
        ]);

        $this->get(route('register.payment'))->assertOk();

        $token = session('registration_wizard.submission_token');
        $this->assertNotEmpty($token);

        $this->post(route('register.submit'), [
            'submission_token' => $token,
            'payment_proof' => UploadedFile::fake()->create('receipt.pdf', 120, 'application/pdf'),
        ])->assertRedirect(route('register.confirmation'));

        $this->post(route('register.submit'), [
            'submission_token' => $token,
            'payment_proof' => UploadedFile::fake()->create('receipt-2.pdf', 120, 'application/pdf'),
        ])->assertRedirect();

        $this->assertDatabaseCount('registrations', 1);
    }

    public function test_users_can_go_back_without_losing_data(): void
    {
        $this->startWizard();
        $this->post(route('register.experience.store'), ['has_tournament_experience' => 'yes']);
        $this->post(route('register.level.store'), ['entry_level' => 'novice']);

        $this->get(route('register.experience'))
            ->assertOk()
            ->assertSee('checked', false)
            ->assertSee('value="yes"', false);

        $this->get(route('register.level'))
            ->assertOk()
            ->assertSee('novice');
    }

    private function startWizard(): void
    {
        $this->get(route('register.welcome'))->assertOk();
        $this->post(route('register.welcome.continue'))->assertRedirect(route('register.experience'));
    }

    private function fakePng(string $name = 'photo.png'): UploadedFile
    {
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');

        return UploadedFile::fake()->createWithContent($name, $png);
    }
}
