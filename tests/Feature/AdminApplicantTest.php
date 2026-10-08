<?php

namespace Tests\Feature;

use App\Enums\PaymentStatus;
use App\Enums\RegistrationStatus;
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
            ->assertSee('Pending Review')
            ->assertSee('Approved')
            ->assertSee('Rejected')
            ->assertSee('Payment Pending');
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
            ->assertDontSee('Maria');
    }

    public function test_pagination_preserves_filters(): void
    {
        Registration::factory()->count(21)->create(['entry_level' => 'novice']);

        $this->actingAs($this->admin())
            ->get(route('admin.applicants.index', ['entry_level' => 'novice']))
            ->assertOk()
            ->assertSee('Showing 1–20 of 21 applicants', false)
            ->assertSee('page=2')
            ->assertSee('entry_level=novice');
    }

    public function test_admin_can_view_applicant_details_and_files(): void
    {
        $registration = Registration::factory()->create([
            'facebook' => 'https://facebook.com/juan',
        ]);
        Storage::disk('local')->put($registration->photo_path, 'photo-bytes');
        Storage::disk('local')->put($registration->payment_proof_path, 'proof-bytes');

        $admin = $this->admin();

        $this->actingAs($admin)
            ->get(route('admin.applicants.show', $registration))
            ->assertOk()
            ->assertSee($registration->registration_number)
            ->assertSee('Approve Registration')
            ->assertSee('Verify Payment')
            ->assertSee('https://facebook.com/juan');

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
        $this->assertNotNull($registration->approved_at);
        $this->assertDatabaseHas('admin_activity_logs', [
            'registration_id' => $registration->id,
            'action' => 'approved',
            'new_status' => 'approved',
        ]);
        Mail::assertSent(RegistrationApproved::class, fn ($mail) => $mail->hasTo($registration->email));
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

    public function test_verify_and_reject_payment(): void
    {
        $registration = Registration::factory()->create();
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.applicants.verify-payment', $registration))
            ->assertRedirect();

        $this->assertSame(PaymentStatus::Verified, $registration->fresh()->payment_status);
        Mail::assertSent(PaymentVerified::class);

        $this->actingAs($admin)
            ->post(route('admin.applicants.reject-payment', $registration), [
                'reason' => 'Screenshot is cropped.',
            ])
            ->assertRedirect();

        $this->assertSame(PaymentStatus::Rejected, $registration->fresh()->payment_status);
        $this->assertSame(1, AdminActivityLog::query()->where('action', 'payment_rejected')->count());
    }

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }
}
