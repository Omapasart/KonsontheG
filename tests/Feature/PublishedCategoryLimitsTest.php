<?php

namespace Tests\Feature;

use App\Enums\EntryLevel;
use App\Enums\SlotStatus;
use App\Models\Registration;
use App\Models\User;
use App\Services\CategoryCapacityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PublishedCategoryLimitsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Mail::fake();
        $this->withoutVite();
    }

    public function test_published_category_totals_are_regular_plus_five_waiting_slots(): void
    {
        $capacity = app(CategoryCapacityService::class);

        $this->assertSame(['capacity' => 36, 'waiting_capacity' => 5], $capacity->limits('beginner'));
        $this->assertSame(['capacity' => 48, 'waiting_capacity' => 5], $capacity->limits('novice'));
        $this->assertSame(['capacity' => 36, 'waiting_capacity' => 5], $capacity->limits('intermediate'));
    }

    public function test_beginner_waiting_list_opens_at_36_and_closes_at_41(): void
    {
        $this->assertWaitingThenClosed(EntryLevel::Beginner, 36, 5);
    }

    public function test_novice_waiting_list_opens_at_48_and_closes_at_53(): void
    {
        $this->assertWaitingThenClosed(EntryLevel::Novice, 48, 5);
    }

    public function test_intermediate_waiting_list_opens_at_36_and_closes_at_41(): void
    {
        $this->assertWaitingThenClosed(EntryLevel::Intermediate, 36, 5);
    }

    public function test_rejected_and_withdrawn_applicants_do_not_count_toward_published_capacity(): void
    {
        Registration::factory()->count(36)->confirmed()->create(['entry_level' => 'beginner']);
        $withdrawn = Registration::query()->where('entry_level', 'beginner')->orderBy('id')->first();
        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.applicants.withdraw', $withdrawn))
            ->assertRedirect();

        $status = app(CategoryCapacityService::class)->statusFor('beginner');
        $this->assertSame(35, $status['confirmed']);
        $this->assertSame(1, $status['regular_remaining']);
        $this->assertFalse($status['is_full']);
        $this->assertSame(SlotStatus::Withdrawn, $withdrawn->fresh()->slot_status);

        $rejected = Registration::factory()->confirmed()->create(['entry_level' => 'beginner']);
        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.applicants.reject', $rejected), [
                'reason' => 'Incomplete details.',
            ])
            ->assertRedirect();

        $status = app(CategoryCapacityService::class)->statusFor('beginner');
        $this->assertSame(35, $status['confirmed']);
        $this->assertFalse($rejected->fresh()->slot_status->occupiesSlot());
    }

    private function assertWaitingThenClosed(EntryLevel $level, int $regular, int $waiting): void
    {
        Registration::factory()->count($regular)->confirmed()->create(['entry_level' => $level->value]);

        $overflow = Registration::factory()->create([
            'entry_level' => $level->value,
            'slot_status' => SlotStatus::PendingVerification,
        ]);

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.applicants.approve', $overflow))
            ->assertRedirect();

        $this->assertSame(SlotStatus::Waiting, $overflow->fresh()->slot_status);
        $this->assertSame(1, $overflow->fresh()->waiting_list_position);

        for ($position = 2; $position <= $waiting; $position++) {
            Registration::factory()->waiting($position)->create(['entry_level' => $level->value]);
        }

        $status = app(CategoryCapacityService::class)->statusFor($level);
        $this->assertTrue($status['is_full']);
        $this->assertSame($regular, $status['confirmed']);
        $this->assertSame($waiting, $status['waiting']);
        $this->assertSame('FULL', $status['availability_label']);

        $this->startWizard();
        $this->post(route('register.experience.store'), ['has_tournament_experience' => 'no']);
        $this->from(route('register.level'))
            ->post(route('register.level.store'), ['entry_level' => $level->value])
            ->assertRedirect(route('register.level'))
            ->assertSessionHasErrors('entry_level');

        $before = Registration::query()->count();
        $this->post(route('register.level.store'), ['entry_level' => $level->value]);
        $this->assertSame($before, Registration::query()->count());
    }

    private function startWizard(): void
    {
        $this->get(route('register.welcome'))->assertOk();
        $this->post(route('register.welcome.continue'))->assertRedirect(route('register.experience'));
    }
}
