<?php

namespace Tests\Feature;

use App\Enums\CategoryTransferStatus;
use App\Enums\RegistrationStatus;
use App\Enums\SlotStatus;
use App\Mail\CategoryTransferConfirmed;
use App\Mail\CategoryTransferDeclined;
use App\Mail\CategoryTransferRequested;
use App\Mail\CategoryTransferUnavailable;
use App\Mail\SlotConfirmed;
use App\Models\CategoryTransferRequest;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class CategoryTransferTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
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

    public function test_admin_can_request_upward_transfer_without_changing_category(): void
    {
        $applicant = Registration::factory()->create([
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'entry_level' => 'beginner',
        ]);
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->from(route('admin.applicants.show', $applicant))
            ->post(route('admin.applicants.category-transfer', $applicant), [
                'requested_category' => 'novice',
            ])
            ->assertRedirect(route('admin.applicants.show', $applicant));

        $this->assertSame('beginner', $applicant->fresh()->entry_level->value);
        $this->assertSame(RegistrationStatus::Pending, $applicant->fresh()->registration_status);
        $this->assertDatabaseHas('category_transfer_requests', [
            'registration_id' => $applicant->id,
            'current_category' => 'beginner',
            'requested_category' => 'novice',
            'status' => 'pending',
        ]);
        Mail::assertSent(CategoryTransferRequested::class, function ($mail) use ($applicant) {
            $html = $mail->render();

            return $mail->hasTo($applicant->email)
                && ! str_contains($html, 'Reason for the proposed transfer')
                && str_contains($html, 'will be rejected automatically');
        });
    }

    public function test_beginner_can_be_proposed_for_intermediate(): void
    {
        $applicant = Registration::factory()->create(['entry_level' => 'beginner']);

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.applicants.category-transfer', $applicant), [
                'requested_category' => 'intermediate',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('category_transfer_requests', [
            'registration_id' => $applicant->id,
            'requested_category' => 'intermediate',
        ]);
    }

    public function test_novice_can_be_proposed_for_intermediate_but_not_beginner(): void
    {
        $applicant = Registration::factory()->create(['entry_level' => 'novice']);
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->from(route('admin.applicants.show', $applicant))
            ->post(route('admin.applicants.category-transfer', $applicant), [
                'requested_category' => 'beginner',
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('requested_category');

        $this->actingAs($admin)
            ->post(route('admin.applicants.category-transfer', $applicant), [
                'requested_category' => 'intermediate',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('category_transfer_requests', [
            'registration_id' => $applicant->id,
            'requested_category' => 'intermediate',
        ]);
    }

    public function test_duplicate_pending_transfer_is_blocked(): void
    {
        $applicant = Registration::factory()->create(['entry_level' => 'beginner']);
        $admin = User::factory()->admin()->create();
        $payload = [
            'requested_category' => 'novice',
        ];

        $this->actingAs($admin)
            ->post(route('admin.applicants.category-transfer', $applicant), $payload)
            ->assertRedirect();

        $this->actingAs($admin)
            ->from(route('admin.applicants.show', $applicant))
            ->post(route('admin.applicants.category-transfer', $applicant), $payload)
            ->assertRedirect()
            ->assertSessionHasErrors('requested_category');

        $this->assertSame(1, CategoryTransferRequest::query()->count());
    }

    public function test_applicant_can_accept_beginner_to_novice_when_slot_is_open(): void
    {
        $applicant = Registration::factory()->create(['entry_level' => 'beginner', 'slot_status' => 'confirmed']);
        $waiting = Registration::factory()->create([
            'entry_level' => 'beginner',
            'slot_status' => 'waiting',
            'waiting_list_position' => 1,
            'confirmed_at' => null,
        ]);
        Registration::factory()->create(['entry_level' => 'beginner', 'slot_status' => 'confirmed']);

        $transfer = $this->requestTransfer($applicant, 'novice');
        $this->respond($transfer, 'accept')
            ->assertOk()
            ->assertSee('Category Transfer Accepted')
            ->assertSee('confirmed', false);

        $this->assertSame('novice', $applicant->fresh()->entry_level->value);
        $this->assertSame(SlotStatus::Confirmed, $applicant->fresh()->slot_status);
        $this->assertSame(RegistrationStatus::Pending, $applicant->fresh()->registration_status);
        $this->assertSame(CategoryTransferStatus::Accepted, $transfer->fresh()->status);
        $this->assertSame(SlotStatus::Confirmed, $waiting->fresh()->slot_status);
        Mail::assertSent(CategoryTransferConfirmed::class, fn ($mail) => $mail->hasTo($applicant->email));
        Mail::assertSent(SlotConfirmed::class, fn ($mail) => $mail->hasTo($waiting->email));
    }

    public function test_accepted_transfer_uses_waiting_list_when_target_confirmed_slots_are_full(): void
    {
        Registration::factory()->count(2)->create(['entry_level' => 'novice', 'slot_status' => 'confirmed']);
        $applicant = Registration::factory()->create(['entry_level' => 'beginner', 'slot_status' => 'confirmed']);
        $transfer = $this->requestTransfer($applicant, 'novice');

        $this->respond($transfer, 'accept')
            ->assertOk()
            ->assertSee('waiting list');

        $this->assertSame('novice', $applicant->fresh()->entry_level->value);
        $this->assertSame(SlotStatus::Waiting, $applicant->fresh()->slot_status);
        $this->assertSame(1, $applicant->fresh()->waiting_list_position);
    }

    public function test_accepted_transfer_is_blocked_when_target_category_is_completely_full(): void
    {
        Registration::factory()->count(2)->create(['entry_level' => 'novice', 'slot_status' => 'confirmed']);
        Registration::factory()->count(2)->create([
            'entry_level' => 'novice',
            'slot_status' => 'waiting',
            'confirmed_at' => null,
        ]);
        $applicant = Registration::factory()->create(['entry_level' => 'beginner', 'slot_status' => 'confirmed']);
        $admin = User::factory()->admin()->create(['email' => 'admin-transfer@example.com']);
        $transfer = $this->requestTransfer($applicant, 'novice', $admin);

        $this->respond($transfer, 'accept')
            ->assertOk()
            ->assertSee('currently full');

        $this->assertSame('beginner', $applicant->fresh()->entry_level->value);
        $this->assertSame(CategoryTransferStatus::Pending, $transfer->fresh()->status);
        Mail::assertSent(CategoryTransferUnavailable::class, fn ($mail) => $mail->hasTo($admin->email));
    }

    public function test_applicant_can_decline_and_keep_current_category(): void
    {
        $applicant = Registration::factory()->create([
            'entry_level' => 'beginner',
            'slot_status' => 'confirmed',
            'registration_status' => RegistrationStatus::Pending,
        ]);
        $waiting = Registration::factory()->create([
            'entry_level' => 'beginner',
            'slot_status' => 'waiting',
            'waiting_list_position' => 1,
            'confirmed_at' => null,
        ]);
        Registration::factory()->create(['entry_level' => 'beginner', 'slot_status' => 'confirmed']);
        $transfer = $this->requestTransfer($applicant, 'novice');

        $this->respond($transfer, 'decline')
            ->assertOk()
            ->assertSee('registration has been rejected');

        $applicant = $applicant->fresh();
        $this->assertSame('beginner', $applicant->entry_level->value);
        $this->assertSame(RegistrationStatus::Rejected, $applicant->registration_status);
        $this->assertSame(SlotStatus::Withdrawn, $applicant->slot_status);
        $this->assertSame(CategoryTransferStatus::Declined, $transfer->fresh()->status);
        $this->assertSame(SlotStatus::Confirmed, $waiting->fresh()->slot_status);
        Mail::assertSent(CategoryTransferDeclined::class, fn ($mail) => $mail->hasTo($applicant->email));
        Mail::assertSent(SlotConfirmed::class, fn ($mail) => $mail->hasTo($waiting->email));
    }

    public function test_registration_actions_show_only_approve_and_transfer_category(): void
    {
        $applicant = Registration::factory()->create(['entry_level' => 'beginner']);

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.applicants.show', $applicant))
            ->assertOk()
            ->assertSee('Approve Registration')
            ->assertSee('Transfer Category')
            ->assertDontSee('Reason for Transfer')
            ->assertDontSee('Reject Registration')
            ->assertDontSee('Request Category Transfer');
    }

    public function test_unsigned_or_forged_transfer_links_are_rejected(): void
    {
        $applicant = Registration::factory()->create(['entry_level' => 'beginner']);
        $transfer = $this->requestTransfer($applicant, 'novice');

        $this->get(route('transfers.show', ['transfer' => $transfer, 'token' => $transfer->token]))
            ->assertForbidden();

        $this->post(route('transfers.accept', $transfer), ['token' => 'forged-token'])
            ->assertForbidden();

        $this->assertSame('beginner', $applicant->fresh()->entry_level->value);
        $this->assertSame(CategoryTransferStatus::Pending, $transfer->fresh()->status);
    }

    public function test_pending_applicant_can_accept_transfer_without_confirming_a_slot(): void
    {
        Registration::factory()->count(2)->create(['entry_level' => 'novice', 'slot_status' => 'confirmed']);
        $applicant = Registration::factory()->create([
            'entry_level' => 'beginner',
            'slot_status' => 'pending_verification',
            'registration_status' => RegistrationStatus::Pending,
        ]);
        $transfer = $this->requestTransfer($applicant, 'novice');

        $this->respond($transfer, 'accept')->assertOk();

        $applicant = $applicant->fresh();
        $this->assertSame('novice', $applicant->entry_level->value);
        $this->assertSame(SlotStatus::PendingVerification, $applicant->slot_status);
        $this->assertSame(RegistrationStatus::Pending, $applicant->registration_status);
        $this->assertSame(2, app(\App\Services\CategoryCapacityService::class)->statusFor('novice')['confirmed']);
        $this->assertSame(0, app(\App\Services\CategoryCapacityService::class)->statusFor('novice')['waiting']);
    }

    public function test_applicants_table_shows_pending_transfer_badge(): void
    {
        $applicant = Registration::factory()->create([
            'first_name' => 'Juan',
            'entry_level' => 'beginner',
        ]);
        $this->requestTransfer($applicant, 'novice');

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.applicants.index'))
            ->assertOk()
            ->assertSee('Pending');
    }

    private function requestTransfer(Registration $applicant, string $target, ?User $admin = null): CategoryTransferRequest
    {
        $admin ??= User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.applicants.category-transfer', $applicant), [
                'requested_category' => $target,
            ])
            ->assertRedirect();

        return CategoryTransferRequest::query()->where('registration_id', $applicant->id)->latest('id')->firstOrFail();
    }

    private function respond(CategoryTransferRequest $transfer, string $action)
    {
        $url = URL::temporarySignedRoute(
            'transfers.show',
            now()->addDay(),
            ['transfer' => $transfer, 'token' => $transfer->token]
        );

        $this->get($url)
            ->assertOk()
            ->assertSee('Do you agree to transfer')
            ->assertDontSee('Reason for the proposed transfer')
            ->assertSee('registration will be rejected automatically');

        return $this->post(route('transfers.'.$action, $transfer), [
            'token' => $transfer->token,
        ]);
    }
}
