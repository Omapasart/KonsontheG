<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminUserSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_admin_accounts_without_duplicating_them(): void
    {
        config([
            'admins.accounts' => [
                [
                    'name' => 'Admin Dane - Konsonthego',
                    'email' => 'aerickadane@gmail.com',
                    'password' => 'test-dane-password',
                ],
                [
                    'name' => 'Admin JD NEL - Konsonthego',
                    'email' => 'jdawitan012799@gmail.com',
                    'password' => 'test-jd-password',
                ],
                [
                    'name' => 'Admin Iso - Konsonthego',
                    'email' => 'isobelzara.aldeguer@gmail.com',
                    'password' => 'test-iso-password',
                ],
            ],
        ]);

        $this->seed(AdminUserSeeder::class);
        $this->seed(AdminUserSeeder::class);

        $this->assertSame(3, User::query()->count());

        foreach ([
            'aerickadane@gmail.com' => ['Admin Dane - Konsonthego', 'test-dane-password'],
            'jdawitan012799@gmail.com' => ['Admin JD NEL - Konsonthego', 'test-jd-password'],
            'isobelzara.aldeguer@gmail.com' => ['Admin Iso - Konsonthego', 'test-iso-password'],
        ] as $email => [$name, $password]) {
            $admin = User::query()->where('email', $email)->first();
            $this->assertNotNull($admin);
            $this->assertTrue($admin->is_admin);
            $this->assertSame($name, $admin->name);
            $this->assertTrue(Hash::check($password, $admin->password));
        }
    }

    public function test_seeder_does_not_overwrite_existing_admin_details(): void
    {
        $existing = User::factory()->admin()->create([
            'name' => 'Existing Dane',
            'email' => 'aerickadane@gmail.com',
            'password' => 'original-password',
        ]);

        config([
            'admins.accounts' => [
                [
                    'name' => 'Admin Dane - Konsonthego',
                    'email' => 'aerickadane@gmail.com',
                    'password' => 'replacement-password',
                ],
            ],
        ]);

        $this->seed(AdminUserSeeder::class);

        $existing->refresh();
        $this->assertSame(1, User::query()->count());
        $this->assertSame('Existing Dane', $existing->name);
        $this->assertTrue($existing->is_admin);
        $this->assertTrue(Hash::check('original-password', $existing->password));
        $this->assertFalse(Hash::check('replacement-password', $existing->password));
    }

    public function test_seeded_admins_can_log_in_and_non_admins_cannot_access_dashboard(): void
    {
        config([
            'admins.accounts' => [
                [
                    'name' => 'Admin Iso - Konsonthego',
                    'email' => 'isobelzara.aldeguer@gmail.com',
                    'password' => 'iso-login-password',
                ],
            ],
        ]);

        $this->seed(AdminUserSeeder::class);
        $this->withoutVite();

        $this->post(route('admin.login.store'), [
            'email' => 'isobelzara.aldeguer@gmail.com',
            'password' => 'iso-login-password',
        ])->assertRedirect(route('admin.dashboard'));

        $this->get(route('admin.dashboard'))->assertOk();
        $this->get(route('admin.applicants.index'))->assertOk();

        $this->post(route('admin.logout'));

        $applicant = User::factory()->create([
            'email' => 'player@example.com',
            'password' => 'player-password',
            'is_admin' => false,
        ]);

        $this->post(route('admin.login.store'), [
            'email' => $applicant->email,
            'password' => 'player-password',
        ])->assertSessionHasErrors('email');

        $this->actingAs($applicant)
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }
}
