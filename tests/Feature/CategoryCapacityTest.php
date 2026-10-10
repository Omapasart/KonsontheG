<?php

namespace Tests\Feature;

use App\Enums\RegistrationStatus;
use App\Enums\SlotStatus;
use App\Mail\RegistrationApproved;
use App\Mail\RegistrationReceived;
use App\Mail\RegistrationWithdrawn;
use App\Mail\SlotConfirmed;
use App\Mail\WaitingListPlaced;
use App\Models\AdminActivityLog;
use App\Models\Registration;
use App\Models\User;
use App\Services\CategoryCapacityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CategoryCapacityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Mail::fake();
        $this->withoutVite();
        config([
            'tournament.categories.beginner.capacity' => 2,
            'tournament.categories.beginner.waiting_capacity' => 2,
            'tournament.categories.novice.capacity' => 2,
            'tournament.categories.novice.waiting_capacity' => 2,
            'tournament.categories.intermediate.capacity' => 2,
            'tournament.categories.intermediate.waiting_capacity' => 2,
        ]);
    }

    public function test_published_tournament_limits(): void
    {
        $defaults = require config_path('tournament.php');

        $this->assertSame(36, $defaults['categories']['beginner']['capacity']);
        $this->assertSame(5, $defaults['categories']['beginner']['waiting_capacity']);
        $this->assertSame(48, $defaults['categories']['novice']['capacity']);
        $this->assertSame(5, $defaults['categories']['novice']['waiting_capacity']);
        $this->assertSame(36, $defaults['categories']['intermediate']['capacity']);
        $this->assertSame(5, $defaults['categories']['intermediate']['waiting_capacity']);
    }

    public function test_confirmed_then_waiting_then_closed_per_category(): void
    {
        $capacity = app(CategoryCapacityService::class);

        $this->assertSame(SlotStatus::Confirmed, $capacity->claim('beginner')['slot_status']);
        Registration::factory()->create(['entry_level' => 'beginner', 'slot_status' => 'confirmed']);
        $this->assertSame(SlotStatus::Confirmed, $capacity->claim('beginner')['slot_status']);
        Registration::factory()->create(['entry_level' => 'beginner', 'slot_status' => 'confirmed']);

        $waiting = $capacity->claim('beginner');
        $this->assertSame(SlotStatus::Waiting, $waiting['slot_status']);
        $this->assertSame(1, $waiting['waiting_list_position']);
        Registration::factory()->create([
            'entry_level' => 'beginner',
            'slot_status' => 'waiting',
            'waiting_list_position' => 1,
            'confirmed_at' => null,
        ]);

        $waitingTwo = $capacity->claim('beginner');
        $this->assertSame(2, $waitingTwo['waiting_list_position']);
        Registration::factory()->create([
            'entry_level' => 'beginner',
            'slot_status' => 'waiting',
            'waiting_list_position' => 2,
            'confirmed_at' => null,
        ]);

        try {
            $capacity->claim('beginner');
            $this->fail('Beginner category should be full.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('entry_level', $exception->errors());
        }

        $novice = $capacity->claim('novice');
        $this->assertSame(SlotStatus::Confirmed, $novice['slot_status']);
    }

    public function test_withdrawing_confirmed_promotes_first_waiting_applicant(): void
    {
        $confirmed = Registration::factory()->create([
            'first_name' => 'Confirmed',
            'last_name' => 'Player',
            'entry_level' => 'beginner',
            'slot_status' => 'confirmed',
            'registration_status' => RegistrationStatus::Approved,
        ]);
        Registration::factory()->create(['entry_level' => 'beginner', 'slot_status' => 'confirmed']);

        $firstWaiting = Registration::factory()->create([
            'first_name' => 'Juan',
            'last_name' => 'Waiting',
            'entry_level' => 'beginner',
            'slot_status' => 'waiting',
            'waiting_list_position' => 1,
            'confirmed_at' => null,
        ]);
        $secondWaiting = Registration::factory()->create([
            'first_name' => 'Maria',
            'last_name' => 'Waiting',
            'entry_level' => 'beginner',
            'slot_status' => 'waiting',
            'waiting_list_position' => 2,
            'confirmed_at' => null,
        ]);

        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)
            ->get(route('admin.applicants.show', $confirmed))
            ->assertOk()
            ->assertSee('Withdraw Applicant')
            ->assertSee('Confirm Withdrawal');

        $this->actingAs($admin)
            ->post(route('admin.applicants.withdraw', $confirmed))
            ->assertRedirect();

        $this->assertSame(SlotStatus::Withdrawn, $confirmed->fresh()->slot_status);
        $this->assertNotNull($confirmed->fresh()->withdrawn_at);
        $this->assertSame(RegistrationStatus::Approved, $confirmed->fresh()->registration_status);
        $this->assertDatabaseHas('registrations', ['id' => $confirmed->id]);
        $this->assertSame(SlotStatus::Confirmed, $firstWaiting->fresh()->slot_status);
        $this->assertNull($firstWaiting->fresh()->waiting_list_position);
        $this->assertNotNull($firstWaiting->fresh()->confirmed_at);
        $this->assertSame(SlotStatus::Waiting, $secondWaiting->fresh()->slot_status);
        $this->assertSame(1, $secondWaiting->fresh()->waiting_list_position);
        Mail::assertSent(RegistrationWithdrawn::class, fn ($mail) => $mail->hasTo($confirmed->email));
        Mail::assertSent(SlotConfirmed::class, fn ($mail) => $mail->hasTo($firstWaiting->email));
        $this->assertDatabaseHas('admin_activity_logs', [
            'registration_id' => $confirmed->id,
            'action' => 'withdrawn',
            'old_status' => 'confirmed',
            'new_status' => 'withdrawn',
        ]);
        $this->assertTrue(
            AdminActivityLog::query()
                ->where('registration_id', $confirmed->id)
                ->where('action', 'withdrawn')
                ->where('remarks', 'like', '%'.$firstWaiting->registration_number.'%')
                ->exists()
        );

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertSee('2 / 2 confirmed')
            ->assertSee('1 / 2 waiting')
            ->assertSee('WAITING LIST ONLY');

        $this->actingAs($admin)
            ->get(route('admin.applicants.show', $confirmed))
            ->assertOk()
            ->assertSee('Withdrawn')
            ->assertSee('Withdrawn At')
            ->assertDontSee('Withdraw Applicant', false);

        $this->actingAs($admin)
            ->from(route('admin.applicants.show', $confirmed))
            ->post(route('admin.applicants.withdraw', $confirmed))
            ->assertRedirect()
            ->assertSessionHasErrors('slot_status');
    }

    public function test_withdrawing_confirmed_without_waiting_list_frees_a_regular_slot(): void
    {
        $confirmed = Registration::factory()->create([
            'entry_level' => 'beginner',
            'slot_status' => 'confirmed',
        ]);
        Registration::factory()->create(['entry_level' => 'beginner', 'slot_status' => 'confirmed']);

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.applicants.withdraw', $confirmed))
            ->assertRedirect();

        $this->assertSame(SlotStatus::Withdrawn, $confirmed->fresh()->slot_status);
        $this->assertSame(1, app(CategoryCapacityService::class)->statusFor('beginner')['confirmed']);
        $this->assertSame(1, app(CategoryCapacityService::class)->statusFor('beginner')['regular_remaining']);

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.dashboard'))
            ->assertSee('1 / 2 confirmed')
            ->assertSee('1 regular remaining')
            ->assertSee('OPEN');
    }

    public function test_manual_promote_is_blocked_when_no_confirmed_slot_is_free(): void
    {
        Registration::factory()->count(2)->create(['entry_level' => 'beginner', 'slot_status' => 'confirmed']);
        $waiting = Registration::factory()->create([
            'entry_level' => 'beginner',
            'slot_status' => 'waiting',
            'waiting_list_position' => 1,
            'confirmed_at' => null,
        ]);

        $this->actingAs(User::factory()->admin()->create())
            ->from(route('admin.applicants.show', $waiting))
            ->post(route('admin.applicants.promote', $waiting))
            ->assertRedirect(route('admin.applicants.show', $waiting))
            ->assertSessionHasErrors('slot_status');

        $this->assertSame(SlotStatus::Waiting, $waiting->fresh()->slot_status);
    }

    public function test_waiting_list_page_lists_queue_order(): void
    {
        Registration::factory()->create([
            'first_name' => 'Second',
            'entry_level' => 'beginner',
            'slot_status' => 'waiting',
            'waiting_list_position' => 2,
            'confirmed_at' => null,
        ]);
        Registration::factory()->create([
            'first_name' => 'First',
            'entry_level' => 'beginner',
            'slot_status' => 'waiting',
            'waiting_list_position' => 1,
            'confirmed_at' => null,
        ]);

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.waiting-list', ['entry_level' => 'beginner']))
            ->assertOk()
            ->assertSeeInOrder(['First', 'Second']);
    }

    public function test_entry_level_page_disables_full_categories_and_allows_waiting_list_only(): void
    {
        Registration::factory()->count(2)->create(['entry_level' => 'beginner', 'slot_status' => 'confirmed']);
        Registration::factory()->count(2)->create([
            'entry_level' => 'beginner',
            'slot_status' => 'waiting',
            'confirmed_at' => null,
        ]);
        Registration::factory()->count(2)->create(['entry_level' => 'novice', 'slot_status' => 'confirmed']);

        $this->startWizard();
        $this->post(route('register.experience.store'), ['has_tournament_experience' => 'no']);

        $this->get(route('register.level'))
            ->assertOk()
            ->assertSee('FULL — REGISTRATION CLOSED')
            ->assertSee('WAITING LIST ONLY')
            ->assertSee('0 regular slots remaining')
            ->assertSee('2 waiting-list slots remaining')
            ->assertDontSee('You may still apply');

        $this->from(route('register.level'))
            ->post(route('register.level.store'), ['entry_level' => 'beginner'])
            ->assertRedirect(route('register.level'))
            ->assertSessionHasErrors('entry_level');

        $this->post(route('register.level.store'), ['entry_level' => 'novice'])
            ->assertRedirect(route('register.personal'))
            ->assertSessionHas('capacity_notice', 'waiting');
    }

    public function test_registration_is_closed_when_all_categories_are_full(): void
    {
        foreach (['beginner', 'novice', 'intermediate'] as $level) {
            Registration::factory()->count(2)->create(['entry_level' => $level, 'slot_status' => 'confirmed']);
            Registration::factory()->count(2)->create([
                'entry_level' => $level,
                'slot_status' => 'waiting',
                'confirmed_at' => null,
            ]);
        }

        $this->startWizard();
        $this->post(route('register.experience.store'), ['has_tournament_experience' => 'no']);

        $this->get(route('register.level'))
            ->assertOk()
            ->assertSee('All tournament categories are fully booked, including their waiting lists. Registration is now closed. Thank you for your interest in KONSONTHEGO Tournament.')
            ->assertSee('Continue', false);

        $this->from(route('register.level'))
            ->post(route('register.level.store'), ['entry_level' => 'intermediate'])
            ->assertRedirect(route('register.level'))
            ->assertSessionHasErrors('entry_level');
    }

    public function test_final_waiting_slot_cannot_be_assigned_to_two_verified_applicants(): void
    {
        Registration::factory()->count(2)->create(['entry_level' => 'beginner', 'slot_status' => 'confirmed']);
        Registration::factory()->create([
            'entry_level' => 'beginner',
            'slot_status' => 'waiting',
            'waiting_list_position' => 1,
            'confirmed_at' => null,
        ]);
        $first = Registration::factory()->create([
            'entry_level' => 'beginner',
            'slot_status' => 'pending_verification',
            'email' => 'first.final@example.com',
        ]);
        $second = Registration::factory()->create([
            'entry_level' => 'beginner',
            'slot_status' => 'pending_verification',
            'email' => 'second.final@example.com',
        ]);

        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->post(route('admin.applicants.approve', $first))->assertRedirect();
        $this->actingAs($admin)
            ->from(route('admin.applicants.show', $second))
            ->post(route('admin.applicants.approve', $second))
            ->assertRedirect(route('admin.applicants.show', $second))
            ->assertSessionHasErrors('entry_level');

        $this->assertSame(SlotStatus::Waiting, $first->fresh()->slot_status);
        $this->assertSame(2, $first->fresh()->waiting_list_position);
        $this->assertSame(SlotStatus::PendingVerification, $second->fresh()->slot_status);
        $status = app(CategoryCapacityService::class)->statusFor('beginner');
        $this->assertTrue($status['is_full']);
        $this->assertSame(2, $status['waiting']);
    }

    public function test_submit_is_rejected_if_category_fills_after_level_selection(): void
    {
        Registration::factory()->count(2)->create(['entry_level' => 'beginner', 'slot_status' => 'confirmed']);
        Registration::factory()->create([
            'entry_level' => 'beginner',
            'slot_status' => 'waiting',
            'waiting_list_position' => 1,
            'confirmed_at' => null,
        ]);

        $this->reachPaymentAs('late.slot@example.com');

        Registration::factory()->create([
            'entry_level' => 'beginner',
            'slot_status' => 'waiting',
            'waiting_list_position' => 2,
            'confirmed_at' => null,
        ]);

        $before = Registration::query()->count();

        $this->from(route('register.payment'))
            ->post(route('register.submit'), [
                'submission_token' => session('registration_wizard.submission_token'),
                'payment_proof' => $this->fakePng('receipt.png'),
            ])
            ->assertRedirect(route('register.category-full', ['level' => 'beginner']));

        $this->assertSame($before, Registration::query()->count());
        $this->assertDatabaseMissing('registrations', ['email' => 'late.slot@example.com']);
    }

    public function test_submitted_registration_stays_pending_until_admin_verifies(): void
    {
        $this->completeRegistrationAs('wait.one@example.com');

        $applicant = Registration::query()->where('email', 'wait.one@example.com')->first();
        $this->assertSame(SlotStatus::PendingVerification, $applicant->slot_status);
        $this->assertSame(RegistrationStatus::Pending, $applicant->registration_status);
        $this->assertNull($applicant->waiting_list_position);

        $this->get(route('register.confirmation'))
            ->assertOk()
            ->assertSee('Slot Status: Pending Verification')
            ->assertDontSee('You have successfully secured a tournament slot.');

        Mail::assertSent(RegistrationReceived::class, function (RegistrationReceived $mail) use ($applicant) {
            $html = $mail->render();

            return $mail->hasTo('wait.one@example.com')
                && $mail->hasSubject('Application Received')
                && str_contains($html, 'Pending Verification')
                && str_contains($html, $applicant->registration_number);
        });
        Mail::assertNotSent(WaitingListPlaced::class);
        Mail::assertNotSent(RegistrationApproved::class);
    }

    public function test_admin_verification_confirms_slot_when_regular_capacity_is_available(): void
    {
        $applicant = Registration::factory()->create([
            'entry_level' => 'beginner',
            'slot_status' => 'pending_verification',
            'email' => 'confirm.me@example.com',
        ]);

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.applicants.approve', $applicant))
            ->assertRedirect();

        $applicant->refresh();
        $this->assertSame(SlotStatus::Confirmed, $applicant->slot_status);
        $this->assertSame(RegistrationStatus::Approved, $applicant->registration_status);
        Mail::assertSent(RegistrationApproved::class, fn ($mail) => $mail->hasTo('confirm.me@example.com'));
        Mail::assertNotSent(WaitingListPlaced::class);
        $this->assertSame(1, app(CategoryCapacityService::class)->statusFor('beginner')['confirmed']);
    }

    public function test_admin_verification_places_applicant_on_waiting_list_when_regular_slots_are_full(): void
    {
        Registration::factory()->count(2)->create(['entry_level' => 'beginner', 'slot_status' => 'confirmed']);
        $applicant = Registration::factory()->create([
            'entry_level' => 'beginner',
            'slot_status' => 'pending_verification',
            'email' => 'wait.after.verify@example.com',
        ]);

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.applicants.approve', $applicant))
            ->assertRedirect();

        $applicant->refresh();
        $this->assertSame(SlotStatus::Waiting, $applicant->slot_status);
        $this->assertSame(1, $applicant->waiting_list_position);
        $this->assertSame(RegistrationStatus::Approved, $applicant->registration_status);
        Mail::assertSent(WaitingListPlaced::class, function ($mail) use ($applicant) {
            return $mail->hasTo('wait.after.verify@example.com')
                && $mail->hasSubject('Application Verified — Waiting List')
                && str_contains($mail->render(), 'placed on the waiting list')
                && ! str_contains($mail->render(), 'regular tournament slot has been confirmed');
        });
        Mail::assertNotSent(RegistrationApproved::class);
    }

    public function test_admin_verification_does_not_overbook_when_category_and_waiting_list_are_full(): void
    {
        Registration::factory()->count(2)->create(['entry_level' => 'beginner', 'slot_status' => 'confirmed']);
        Registration::factory()->count(2)->create([
            'entry_level' => 'beginner',
            'slot_status' => 'waiting',
            'confirmed_at' => null,
        ]);
        $applicant = Registration::factory()->create([
            'entry_level' => 'beginner',
            'slot_status' => 'pending_verification',
        ]);

        $this->actingAs(User::factory()->admin()->create())
            ->from(route('admin.applicants.show', $applicant))
            ->post(route('admin.applicants.approve', $applicant))
            ->assertRedirect(route('admin.applicants.show', $applicant))
            ->assertSessionHasErrors('entry_level');

        $applicant->refresh();
        $this->assertSame(SlotStatus::PendingVerification, $applicant->slot_status);
        $this->assertSame(RegistrationStatus::Pending, $applicant->registration_status);
        Mail::assertNotSent(RegistrationApproved::class);
        Mail::assertNotSent(WaitingListPlaced::class);

        $status = app(CategoryCapacityService::class)->statusFor('beginner');
        $this->assertSame(2, $status['confirmed']);
        $this->assertSame(2, $status['waiting']);
    }

    public function test_rejected_applicants_do_not_occupy_active_slots(): void
    {
        $confirmed = Registration::factory()->create([
            'entry_level' => 'beginner',
            'slot_status' => 'confirmed',
            'registration_status' => RegistrationStatus::Approved,
        ]);
        $waiting = Registration::factory()->create([
            'entry_level' => 'beginner',
            'slot_status' => 'waiting',
            'waiting_list_position' => 1,
            'confirmed_at' => null,
        ]);

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.applicants.reject', $confirmed), [
                'reason' => 'Incomplete details.',
            ])
            ->assertRedirect();

        $this->assertSame(RegistrationStatus::Rejected, $confirmed->fresh()->registration_status);
        $this->assertFalse($confirmed->fresh()->slot_status->occupiesSlot());
        $this->assertSame(SlotStatus::Confirmed, $waiting->fresh()->slot_status);
        $this->assertSame(1, app(CategoryCapacityService::class)->statusFor('beginner')['confirmed']);
        $this->assertSame(0, app(CategoryCapacityService::class)->statusFor('beginner')['waiting']);
    }

    public function test_dashboard_shows_category_capacity_counts(): void
    {
        Registration::factory()->count(2)->create(['entry_level' => 'beginner', 'slot_status' => 'confirmed']);
        Registration::factory()->create([
            'entry_level' => 'beginner',
            'slot_status' => 'waiting',
            'waiting_list_position' => 1,
            'confirmed_at' => null,
        ]);

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('2 / 2 confirmed')
            ->assertSee('1 / 2 waiting')
            ->assertSee('0 regular remaining')
            ->assertSee('1 waiting remaining')
            ->assertSee('WAITING LIST ONLY');
    }

    private function startWizard(): void
    {
        $this->get(route('register.welcome'))->assertOk();
        $this->post(route('register.welcome.continue'))->assertRedirect(route('register.experience'));
    }

    private function completeRegistrationAs(string $email): void
    {
        $this->reachPaymentAs($email);

        $this->post(route('register.submit'), [
            'submission_token' => session('registration_wizard.submission_token'),
            'payment_proof' => $this->fakePng('receipt.png'),
        ])->assertRedirect(route('register.confirmation'));
    }

    private function reachPaymentAs(string $email): void
    {
        $this->startWizard();
        $this->post(route('register.experience.store'), ['has_tournament_experience' => 'no']);
        $this->post(route('register.level.store'), ['entry_level' => 'beginner']);
        $this->post(route('register.personal.store'), [
            'last_name' => 'Waiter',
            'first_name' => 'One',
            'middle_initial' => 'A',
            'contact_number' => '09171234567',
            'address' => 'Malaybalay City, Bukidnon',
            'email' => $email,
            'photo' => $this->fakePng('player.png'),
        ]);

        $this->get(route('register.payment'))->assertOk();
    }

    private function fakePng(string $name = 'photo.png'): UploadedFile
    {
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');

        return UploadedFile::fake()->createWithContent($name, $png);
    }
}
