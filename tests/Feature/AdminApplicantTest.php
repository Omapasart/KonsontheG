<?php

namespace Tests\Feature;

use App\Enums\PaymentStatus;
use App\Enums\RegistrationStatus;
use App\Enums\SlotStatus;
use App\Mail\PaymentRejected;
use App\Mail\PaymentVerified;
use App\Mail\RegistrationApproved;
use App\Mail\RegistrationRejected;
use App\Models\AdminActivityLog;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminApplicantTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Mail::fake();
        $this->withoutVite();
    }

    public function test_guests_are_redirected_to_admin_login(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('admin.login'));
        $this->get(route('admin.applicants.index'))->assertRedirect(route('admin.login'));
    }

    public function test_non_admin_users_cannot_access_admin(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $registration = Registration::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.dashboard'))
            ->assertForbidden();

        $this->actingAs($user)
            ->get(route('admin.applicants.photo', $registration))
            ->assertForbidden();
    }

    public function test_dashboard_shows_live_counts(): void
    {
        Registration::factory()->count(2)->create();
        Registration::factory()->create([
            'registration_status' => RegistrationStatus::Approved,
            'payment_status' => PaymentStatus::Verified,
        ]);
        Registration::factory()->create([
            'registration_status' => RegistrationStatus::Rejected,
        ]);

        $this->actingAs($this->admin())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Total Applicants')
            ->assertSee('4')
            ->assertSee('Pending Verification')
            ->assertSee('Confirmed')
            ->assertSee('Waiting List')
            ->assertSee('Pending Review')
            ->assertSee('Approved')
            ->assertSee('Rejected')
            ->assertSee('Payment Pending')
            ->assertSee('Withdrawn')
            ->assertSee(route('admin.applicants.index', ['slot_status' => 'withdrawn'], false), false)
            ->assertViewHas('withdrawn', 0)
            ->assertSee('Beginner')
            ->assertSee('Novice')
            ->assertSee('Intermediate');
    }

    public function test_dashboard_shows_entry_level_counts_and_filters_applicants(): void
    {
        Registration::factory()->create([
            'first_name' => 'Ana',
            'last_name' => 'BeginnerOne',
            'entry_level' => 'beginner',
            'registration_status' => RegistrationStatus::Approved,
        ]);
        Registration::factory()->create([
            'first_name' => 'Ben',
            'last_name' => 'BeginnerTwo',
            'entry_level' => 'beginner',
            'registration_status' => RegistrationStatus::Pending,
        ]);
        Registration::factory()->create([
            'first_name' => 'Nora',
            'last_name' => 'NoviceOne',
            'entry_level' => 'novice',
            'registration_status' => RegistrationStatus::Pending,
        ]);
        Registration::factory()->create([
            'first_name' => 'Ivy',
            'last_name' => 'IntermediateOne',
            'entry_level' => 'intermediate',
            'registration_status' => RegistrationStatus::Rejected,
        ]);

        $admin = $this->admin();

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee(route('admin.applicants.index', ['entry_level' => 'beginner'], false), false)
            ->assertSee(route('admin.applicants.index', ['entry_level' => 'novice'], false), false)
            ->assertSee(route('admin.applicants.index', ['entry_level' => 'intermediate'], false), false)
            ->assertViewHas('levelStats', function (array $levelStats): bool {
                return $levelStats['beginner']['total'] === 2
                    && $levelStats['beginner']['approved'] === 1
                    && $levelStats['beginner']['pending'] === 1
                    && $levelStats['novice']['total'] === 1
                    && $levelStats['novice']['pending'] === 1
                    && $levelStats['intermediate']['total'] === 1
                    && $levelStats['intermediate']['rejected'] === 1;
            });

        $this->actingAs($admin)
            ->get(route('admin.applicants.index', ['entry_level' => 'beginner']))
            ->assertOk()
            ->assertSee('BeginnerOne')
            ->assertSee('BeginnerTwo')
            ->assertDontSee('NoviceOne')
            ->assertDontSee('IntermediateOne')
            ->assertSee('Showing 1–2 of 2 Beginner applicants', false);
    }

    public function test_dashboard_withdrawn_card_counts_and_filters_applicants(): void
    {
        Registration::factory()->create([
            'first_name' => 'StayOn',
            'last_name' => 'Player',
            'slot_status' => SlotStatus::Confirmed,
            'registration_status' => RegistrationStatus::Approved,
        ]);
        $withdrawn = Registration::factory()->create([
            'first_name' => 'Left',
            'last_name' => 'Tournament',
            'slot_status' => SlotStatus::Withdrawn,
            'registration_status' => RegistrationStatus::Approved,
            'withdrawn_at' => now(),
        ]);

        $admin = $this->admin();

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertViewHas('total', 2)
            ->assertViewHas('approved', 2)
            ->assertViewHas('withdrawn', 1)
            ->assertSee('Withdrawn');

        $this->actingAs($admin)
            ->get(route('admin.applicants.index', ['slot_status' => 'withdrawn']))
            ->assertOk()
            ->assertSee('Left')
            ->assertSee($withdrawn->registration_number)
            ->assertSee('Withdrawn')
            ->assertDontSee('StayOn')
            ->assertSee('Showing 1–1 of 1 withdrawn applicants', false);

        $this->actingAs($admin)
            ->get(route('admin.applicants.index', ['status' => 'withdrawn']))
            ->assertOk()
            ->assertSee('Left')
            ->assertDontSee('StayOn');
    }

    public function test_applicants_can_be_searched_and_filtered(): void
    {
        $match = Registration::factory()->create([
            'last_name' => 'Dela Cruz',
            'first_name' => 'Juan',
            'entry_level' => 'beginner',
            'email' => 'juan@example.com',
        ]);
        Registration::factory()->create([
            'last_name' => 'Santos',
            'first_name' => 'Maria',
            'entry_level' => 'intermediate',
        ]);

        $this->actingAs($this->admin())
            ->get(route('admin.applicants.index', [
                'q' => 'Dela Cruz',
                'entry_level' => 'beginner',
            ]))
            ->assertOk()
            ->assertSee('Juan')
            ->assertSee($match->registration_number)
            ->assertDontSee('Santos');
    }

    public function test_pagination_preserves_filters(): void
    {
        Registration::factory()->count(21)->create(['entry_level' => 'novice']);

        $this->actingAs($this->admin())
            ->get(route('admin.applicants.index', ['entry_level' => 'novice']))
            ->assertOk()
            ->assertSee('Showing 1–20 of 21 Novice applicants', false)
            ->assertSee('page=2')
            ->assertSee('entry_level=novice');
    }

    public function test_admin_can_view_applicant_details_and_files(): void
    {
        $registration = Registration::factory()->create([
            'entry_level' => 'beginner',
        ]);
        Storage::disk('local')->put($registration->photo_path, 'photo-bytes');
        Storage::disk('local')->put($registration->payment_proof_path, 'proof-bytes');

        $admin = $this->admin();

        $this->actingAs($admin)
            ->get(route('admin.applicants.show', $registration))
            ->assertOk()
            ->assertSee($registration->registration_number)
            ->assertSee('Approve Registration')
            ->assertSee('Transfer Category')
            ->assertDontSee('Reject Registration')
            ->assertSee('Verify Payment')
            ->assertDontSee('Facebook');

        $this->actingAs($admin)
            ->get(route('admin.applicants.photo', $registration))
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('admin.applicants.proof.download', $registration))
            ->assertOk();
    }

    public function test_approve_updates_status_logs_activity_and_emails(): void
    {
        $registration = Registration::factory()->create();

        $this->actingAs($this->admin())
            ->post(route('admin.applicants.approve', $registration))
            ->assertRedirect();

        $registration->refresh();

        $this->assertSame(RegistrationStatus::Approved, $registration->registration_status);
        $this->assertSame(SlotStatus::Confirmed, $registration->slot_status);
        $this->assertNotNull($registration->approved_at);
        $this->assertNotNull($registration->confirmed_at);
        $this->assertDatabaseHas('admin_activity_logs', [
            'registration_id' => $registration->id,
            'action' => 'approved',
            'new_status' => 'approved',
        ]);
        Mail::assertSent(RegistrationApproved::class, function ($mail) use ($registration) {
            return $mail->hasTo($registration->email)
                && $mail->hasSubject('Registration Verified — Slot Confirmation')
                && str_contains($mail->render(), 'regular tournament slot has been confirmed');
        });
    }

    public function test_reject_requires_a_reason_then_emails_the_applicant(): void
    {
        $registration = Registration::factory()->create();
        $admin = $this->admin();

        $this->actingAs($admin)
            ->from(route('admin.applicants.show', $registration))
            ->post(route('admin.applicants.reject', $registration), [])
            ->assertSessionHasErrors('reason')
            ->assertRedirect(route('admin.applicants.show', $registration));

        $this->actingAs($admin)
            ->post(route('admin.applicants.reject', $registration), [
                'reason' => 'Unclear photo and incomplete details.',
            ])
            ->assertRedirect();

        $registration->refresh();
        $this->assertSame(RegistrationStatus::Rejected, $registration->registration_status);
        $this->assertSame('Unclear photo and incomplete details.', $registration->rejection_reason);
        Mail::assertSent(RegistrationRejected::class);
    }

    public function test_pending_payment_shows_verify_and_reject_actions(): void
    {
        $registration = Registration::factory()->create(['payment_status' => PaymentStatus::Pending]);

        $this->actingAs($this->admin())
            ->get(route('admin.applicants.show', $registration))
            ->assertOk()
            ->assertSee('Verify Payment')
            ->assertSee('Reject Payment');
    }

    public function test_verify_payment_hides_actions_and_does_not_approve_registration(): void
    {
        $registration = Registration::factory()->create([
            'payment_status' => PaymentStatus::Pending,
            'registration_status' => RegistrationStatus::Pending,
            'slot_status' => SlotStatus::PendingVerification,
        ]);
        $admin = $this->admin();

        $this->actingAs($admin)
            ->from(route('admin.applicants.show', $registration))
            ->post(route('admin.applicants.verify-payment', $registration))
            ->assertRedirect(route('admin.applicants.show', $registration));

        $registration->refresh();
        $this->assertSame(PaymentStatus::Verified, $registration->payment_status);
        $this->assertSame(RegistrationStatus::Pending, $registration->registration_status);
        $this->assertSame(SlotStatus::PendingVerification, $registration->slot_status);
        $this->assertSame($admin->id, $registration->payment_reviewed_by);
        $this->assertNotNull($registration->payment_reviewed_at);
        Mail::assertSent(PaymentVerified::class, function ($mail) use ($registration) {
            $html = $mail->render();

            return $mail->hasTo($registration->email)
                && $mail->hasSubject('KONSONTHEGO Payment Verified')
                && str_contains($html, $registration->fullName())
                && str_contains($html, $registration->registration_number)
                && str_contains($html, $registration->entry_level->label())
                && str_contains($html, 'Payment Status')
                && str_contains($html, 'Verified')
                && str_contains($html, 'Pending Verification');
        });
        $this->assertSame(1, AdminActivityLog::query()->where('action', 'payment_verified')->count());

        $this->actingAs($admin)
            ->get(route('admin.applicants.show', $registration))
            ->assertOk()
            ->assertSee('Payment Verified')
            ->assertSee('Verified by '.$admin->name)
            ->assertDontSee('Verify Payment')
            ->assertDontSee('Reject Payment');

        $this->actingAs($admin)
            ->get(route('admin.applicants.index'))
            ->assertOk()
            ->assertSee('Payment Verified')
            ->assertDontSee('>Verify Payment<', false)
            ->assertDontSee('>Reject Payment<', false);
    }

    public function test_verified_payment_cannot_be_reverified_or_rejected(): void
    {
        $registration = Registration::factory()->create(['payment_status' => PaymentStatus::Pending]);
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.applicants.verify-payment', $registration))
            ->assertRedirect();

        Mail::assertSent(PaymentVerified::class, 1);

        $this->actingAs($admin)
            ->from(route('admin.applicants.show', $registration))
            ->post(route('admin.applicants.verify-payment', $registration))
            ->assertRedirect(route('admin.applicants.show', $registration))
            ->assertSessionHasErrors('payment_status');

        $this->actingAs($admin)
            ->from(route('admin.applicants.show', $registration))
            ->post(route('admin.applicants.reject-payment', $registration), [
                'reason' => 'Screenshot is cropped.',
            ])
            ->assertRedirect(route('admin.applicants.show', $registration))
            ->assertSessionHasErrors('payment_status');

        $this->assertSame(PaymentStatus::Verified, $registration->fresh()->payment_status);
        $this->assertSame(1, AdminActivityLog::query()->where('action', 'payment_verified')->count());
        $this->assertSame(0, AdminActivityLog::query()->where('action', 'payment_rejected')->count());
        Mail::assertSent(PaymentVerified::class, 1);
        Mail::assertNotSent(PaymentRejected::class);
    }

    public function test_payment_verified_email_shows_confirmed_and_waiting_list_slot_status(): void
    {
        foreach ([
            [
                'slot_status' => SlotStatus::Confirmed,
                'entry_level' => 'novice',
                'expected_slot' => 'Confirmed',
                'waiting_list_position' => null,
            ],
            [
                'slot_status' => SlotStatus::Waiting,
                'entry_level' => 'intermediate',
                'expected_slot' => 'Waiting List',
                'waiting_list_position' => 2,
            ],
        ] as $case) {
            Mail::fake();

            $registration = Registration::factory()->create([
                'payment_status' => PaymentStatus::Pending,
                'slot_status' => $case['slot_status'],
                'entry_level' => $case['entry_level'],
                'waiting_list_position' => $case['waiting_list_position'],
                'confirmed_at' => $case['slot_status'] === SlotStatus::Confirmed ? now() : null,
            ]);

            $this->actingAs($this->admin())
                ->post(route('admin.applicants.verify-payment', $registration))
                ->assertRedirect();

            $registration->refresh();
            $this->assertSame(PaymentStatus::Verified, $registration->payment_status);
            $this->assertSame($case['slot_status'], $registration->slot_status);
            $this->assertSame($case['expected_slot'], $registration->slot_status->label());

            Mail::assertSent(PaymentVerified::class, function ($mail) use ($registration, $case) {
                $html = $mail->render();

                return $mail->hasTo($registration->email)
                    && str_contains($html, $registration->fullName())
                    && str_contains($html, $registration->registration_number)
                    && str_contains($html, $registration->entry_level->label())
                    && str_contains($html, 'Verified')
                    && str_contains($html, $case['expected_slot']);
            });
        }
    }

    public function test_pending_payment_can_still_be_rejected(): void
    {
        $registration = Registration::factory()->create(['payment_status' => PaymentStatus::Pending]);
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.applicants.reject-payment', $registration), [
                'reason' => 'Screenshot is cropped.',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame(PaymentStatus::Rejected, $registration->fresh()->payment_status);
        Mail::assertSent(PaymentRejected::class);
        $this->assertSame(1, AdminActivityLog::query()->where('action', 'payment_rejected')->count());

        $this->actingAs($admin)
            ->get(route('admin.applicants.show', $registration))
            ->assertOk()
            ->assertSee('Verify Payment')
            ->assertSee('Reject Payment');
    }

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }
}
