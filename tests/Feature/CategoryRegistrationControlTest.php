<?php

namespace Tests\Feature;

use App\Models\AdminActivityLog;
use App\Models\CategoryLock;
use App\Models\Registration;
use App\Models\User;
use App\Services\CategoryCapacityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CategoryRegistrationControlTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        $this->withoutVite();
    }

    public function test_admin_can_close_and_reopen_a_category_without_changing_applicants(): void
    {
        $applicant = Registration::factory()->confirmed()->create(['entry_level' => 'novice']);
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.categories.index'))
            ->assertOk()
            ->assertSee('Open / Close Categories')
            ->assertSee('Close Novice');

        $this->actingAs($admin)
            ->put(route('admin.categories.update', 'novice'), ['closed' => '1'])
            ->assertRedirect();

        $this->assertTrue(CategoryLock::query()->find('novice')->registration_closed);
        $this->assertSame($applicant->email, $applicant->fresh()->email);
        $this->assertSame('confirmed', $applicant->fresh()->slot_status->value);
        $this->assertTrue(app(CategoryCapacityService::class)->statusFor('novice')['closed']);
        $this->assertFalse(app(CategoryCapacityService::class)->statusFor('beginner')['closed']);
        $this->assertDatabaseHas('admin_activity_logs', [
            'action' => 'category_closed',
            'new_status' => 'closed',
        ]);

        $this->startWizard();
        $this->post(route('register.experience.store'), ['has_tournament_experience' => 'yes']);
        $this->from(route('register.level'))
            ->post(route('register.level.store'), ['entry_level' => 'novice'])
            ->assertRedirect(route('register.level'))
            ->assertSessionHasErrors('entry_level');

        $this->assertSame(1, Registration::query()->count());

        $this->actingAs($admin)
            ->put(route('admin.categories.update', 'novice'), ['closed' => '0'])
            ->assertRedirect();

        $this->assertFalse(CategoryLock::query()->find('novice')->registration_closed);
        $this->assertFalse(app(CategoryCapacityService::class)->statusFor('novice')['closed']);
        $this->assertSame(1, AdminActivityLog::query()->where('action', 'category_opened')->count());
    }

    public function test_guests_cannot_change_category_registration(): void
    {
        $this->get(route('admin.categories.index'))->assertRedirect(route('admin.login'));
        $this->put(route('admin.categories.update', 'beginner'), ['closed' => '1'])
            ->assertRedirect(route('admin.login'));
        $this->assertFalse(CategoryLock::query()->find('beginner')->registration_closed);
    }

    public function test_admin_can_change_confirmed_and_waiting_limits_without_changing_applicants(): void
    {
        $applicant = Registration::factory()->confirmed()->create(['entry_level' => 'intermediate']);
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->put(route('admin.categories.limits', 'intermediate'), [
                'confirmed_capacity' => 40,
                'waiting_capacity' => 8,
            ])
            ->assertRedirect();

        $lock = CategoryLock::query()->find('intermediate');
        $this->assertSame(40, $lock->confirmed_capacity);
        $this->assertSame(8, $lock->waiting_capacity);
        $this->assertSame('confirmed', $applicant->fresh()->slot_status->value);
        $this->assertSame(1, Registration::query()->count());

        $status = app(CategoryCapacityService::class)->statusFor('intermediate');
        $this->assertSame(40, $status['capacity']);
        $this->assertSame(8, $status['waiting_capacity']);
        $this->assertSame(39, $status['regular_remaining']);
        $this->assertDatabaseHas('admin_activity_logs', [
            'action' => 'category_limits_updated',
            'new_status' => '40/8',
        ]);
    }

    private function startWizard(): void
    {
        $this->get(route('register.welcome'))->assertOk();
        $this->post(route('register.welcome.continue'))->assertRedirect(route('register.experience'));
    }
}
