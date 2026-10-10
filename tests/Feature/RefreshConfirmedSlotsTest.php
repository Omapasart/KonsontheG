<?php

namespace Tests\Feature;

use App\Enums\PaymentStatus;
use App\Enums\RegistrationStatus;
use App\Enums\SlotStatus;
use App\Mail\RegistrationWithdrawn;
use App\Mail\SlotConfirmed;
use App\Mail\WaitingListTransferred;
use Illuminate\Mail\Mailable;
use App\Models\AdminActivityLog;
use App\Models\Registration;
use App\Models\User;
use App\Services\CategoryCapacityService;
use App\Services\RefreshConfirmedSlotsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RefreshConfirmedSlotsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        Storage::fake('local');
        $this->withoutVite();
    }

    public function test_refresh_confirmation_page_explains_waiting_list_becomes_pending(): void
    {
        Registration::factory()->count(2)->confirmed()->create(['entry_level' => 'beginner']);
        Registration::factory()->waiting(1)->create(['entry_level' => 'beginner']);

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.confirmed-slots.refresh'))
            ->assertOk()
            ->assertSee('move the waiting list to pending')
            ->assertSee('pending slot verification')
            ->assertSee('pending payment verification')
            ->assertSee('will not automatically receive a confirmed slot')
            ->assertSee('transferred to a regular slot')
            ->assertSee('Confirmed applicants are not emailed');
    }

    public function test_refresh_requires_confirmation_and_admin_access(): void
    {
        $this->get(route('admin.confirmed-slots.refresh'))->assertRedirect(route('admin.login'));

        $this->actingAs(User::factory()->create(['is_admin' => false]))
            ->post(route('admin.confirmed-slots.refresh.store'))
            ->assertForbidden();

        $this->actingAs(User::factory()->admin()->create())
            ->from(route('admin.confirmed-slots.refresh'))
            ->post(route('admin.confirmed-slots.refresh.store'))
            ->assertRedirect(route('admin.confirmed-slots.refresh'))
            ->assertSessionHasErrors('confirm');
    }

    public function test_all_waiting_list_applicants_become_pending_not_confirmed(): void
    {
        Storage::disk('local')->put('registrations/photos/old.png', 'photo');
        Storage::disk('local')->put('registrations/photos/wait.png', 'wait-photo');

        $confirmed = Registration::factory()->confirmed()->create([
            'entry_level' => 'beginner',
            'photo_path' => 'registrations/photos/old.png',
            'email' => 'old.confirmed@example.com',
        ]);
        $first = Registration::factory()->waiting(1)->create([
            'entry_level' => 'beginner',
            'photo_path' => 'registrations/photos/wait.png',
            'email' => 'first.wait@example.com',
        ]);
        $second = Registration::factory()->waiting(2)->create([
            'entry_level' => 'beginner',
            'email' => 'second.wait@example.com',
        ]);
        $third = Registration::factory()->waiting(3)->create([
            'entry_level' => 'beginner',
            'email' => 'third.wait@example.com',
        ]);

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.confirmed-slots.refresh.store'), ['confirm' => '1'])
            ->assertRedirect(route('admin.dashboard'));

        $this->assertSame(3, Registration::query()->count());
        $this->assertDatabaseMissing('registrations', ['id' => $confirmed->id]);

        foreach ([$first, $second, $third] as $applicant) {
            $fresh = $applicant->fresh();
            $this->assertSame(SlotStatus::PendingVerification, $fresh->slot_status);
            $this->assertSame(RegistrationStatus::Pending, $fresh->registration_status);
            $this->assertSame(PaymentStatus::Pending, $fresh->payment_status);
            $this->assertNull($fresh->payment_reviewed_by);
            $this->assertNull($fresh->payment_reviewed_at);
            $this->assertNull($fresh->waiting_list_position);
            $this->assertNull($fresh->confirmed_at);
        }

        $this->assertSame(0, Registration::query()->where('slot_status', SlotStatus::Confirmed)->count());
        $this->assertSame(0, app(CategoryCapacityService::class)->statusFor('beginner')['confirmed']);
        $this->assertTrue(Storage::disk('local')->exists('registrations/photos/wait.png'));
        Mail::assertNotSent(SlotConfirmed::class);
        Mail::assertNotSent(RegistrationWithdrawn::class);
        Mail::assertSent(WaitingListTransferred::class, 3);
        Mail::assertSent(WaitingListTransferred::class, fn ($mail) => $mail->hasTo('first.wait@example.com'));
        Mail::assertSent(WaitingListTransferred::class, fn ($mail) => $mail->hasTo('second.wait@example.com'));
        Mail::assertSent(WaitingListTransferred::class, fn ($mail) => $mail->hasTo('third.wait@example.com'));
        Mail::assertNotSent(fn (Mailable $mail) => $mail->hasTo('old.confirmed@example.com'));
        $this->assertSame(3, AdminActivityLog::query()->where('action', 'moved_to_pending')->count());
    }

    public function test_rejected_waiting_applicants_are_not_moved_to_pending(): void
    {
        Registration::factory()->confirmed()->create(['entry_level' => 'beginner']);
        Registration::factory()->waiting(1)->create([
            'entry_level' => 'beginner',
            'registration_status' => RegistrationStatus::Rejected,
            'email' => 'rejected@example.com',
        ]);
        $eligible = Registration::factory()->waiting(2)->create([
            'entry_level' => 'beginner',
            'email' => 'eligible@example.com',
        ]);

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.confirmed-slots.refresh.store'), ['confirm' => '1'])
            ->assertRedirect(route('admin.dashboard'));

        $this->assertSame(SlotStatus::PendingVerification, $eligible->fresh()->slot_status);
        $this->assertSame(SlotStatus::Waiting, Registration::query()->where('email', 'rejected@example.com')->first()->slot_status);
        Mail::assertSent(WaitingListTransferred::class, 1);
        Mail::assertSent(WaitingListTransferred::class, fn ($mail) => $mail->hasTo('eligible@example.com'));
        Mail::assertNotSent(WaitingListTransferred::class, fn ($mail) => $mail->hasTo('rejected@example.com'));
        Mail::assertNotSent(SlotConfirmed::class);
    }

    public function test_failed_refresh_rolls_back_and_sends_no_email(): void
    {
        Registration::factory()->confirmed()->create(['entry_level' => 'beginner']);
        Registration::factory()->waiting(1)->create(['entry_level' => 'beginner']);

        $listener = function (): void {
            throw new \RuntimeException('Forced refresh failure.');
        };
        Registration::deleting($listener);

        try {
            app(RefreshConfirmedSlotsService::class)->refresh(User::factory()->admin()->create());
            $this->fail('Refresh should have failed.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Forced refresh failure.', $exception->getMessage());
        } finally {
            Registration::getEventDispatcher()->forget('eloquent.deleting: '.Registration::class);
        }

        Mail::assertNothingSent();
        $this->assertSame(2, Registration::query()->count());
        $this->assertSame(1, Registration::query()->where('slot_status', SlotStatus::Waiting)->count());
    }
}
