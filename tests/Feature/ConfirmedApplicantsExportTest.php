<?php

namespace Tests\Feature;

use App\Enums\RegistrationStatus;
use App\Enums\SlotStatus;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use ZipArchive;

class ConfirmedApplicantsExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->withoutVite();
    }

    public function test_guests_cannot_export_confirmed_applicants(): void
    {
        $this->get(route('admin.confirmed-applicants.export'))
            ->assertRedirect(route('admin.login'));
    }

    public function test_export_downloads_only_confirmed_applicants_from_all_categories(): void
    {
        $beginner = Registration::factory()->confirmed()->create([
            'entry_level' => 'beginner',
            'last_name' => 'Santos',
            'first_name' => 'Ana',
            'middle_initial' => 'B',
            'email' => 'ana@example.com',
            'contact_number' => '09170000001',
        ]);
        $novice = Registration::factory()->confirmed()->create([
            'entry_level' => 'novice',
            'last_name' => 'Reyes',
            'first_name' => 'Luis',
            'email' => 'luis@example.com',
        ]);
        $intermediate = Registration::factory()->confirmed()->create([
            'entry_level' => 'intermediate',
            'last_name' => 'Cruz',
            'first_name' => 'Maria',
            'email' => 'maria@example.com',
        ]);

        Registration::factory()->waiting(1)->create(['entry_level' => 'beginner', 'email' => 'wait@example.com']);
        Registration::factory()->create(['slot_status' => SlotStatus::PendingVerification, 'email' => 'pending@example.com']);
        Registration::factory()->create([
            'slot_status' => SlotStatus::Withdrawn,
            'email' => 'gone@example.com',
        ]);
        Registration::factory()->create([
            'registration_status' => RegistrationStatus::Rejected,
            'email' => 'nope@example.com',
        ]);

        $before = Registration::query()->get(['id', 'updated_at', 'slot_status'])->map->toArray();

        $response = $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.confirmed-applicants.export'));

        $filename = 'KONSONTHEGO_Confirmed_Applicants_'.now()->format('Y-m-d').'.xlsx';
        $response->assertOk()->assertDownload($filename);

        $xml = $this->sheetXml($response->streamedContent());

        $this->assertStringContainsString('KONSONTHEGO — Confirmed Applicants', $xml);
        $this->assertStringContainsString('Export date:', $xml);
        $this->assertStringContainsString('Registration Number', $xml);
        $this->assertStringContainsString('autoFilter', $xml);
        $this->assertStringContainsString('state="frozen"', $xml);
        $this->assertStringContainsString($beginner->registration_number, $xml);
        $this->assertStringContainsString('09170000001', $xml);
        $this->assertStringContainsString('Ana B. Santos', $xml);
        $this->assertStringContainsString('Beginner', $xml);
        $this->assertStringContainsString('Novice', $xml);
        $this->assertStringContainsString('Intermediate', $xml);
        $this->assertStringContainsString('ana@example.com', $xml);
        $this->assertStringContainsString('luis@example.com', $xml);
        $this->assertStringContainsString('maria@example.com', $xml);
        $this->assertStringNotContainsString('wait@example.com', $xml);
        $this->assertStringNotContainsString('pending@example.com', $xml);
        $this->assertStringNotContainsString('gone@example.com', $xml);
        $this->assertStringNotContainsString('nope@example.com', $xml);
        $this->assertStringNotContainsString('password', strtolower($xml));
        $this->assertTrue(strpos($xml, 'Santos') < strpos($xml, 'Reyes'));
        $this->assertTrue(strpos($xml, 'Reyes') < strpos($xml, 'Cruz'));

        $this->assertEquals(
            $before,
            Registration::query()->get(['id', 'updated_at', 'slot_status'])->map->toArray()
        );
        $this->assertSame(7, Registration::query()->count());
        $this->assertSame(3, Registration::query()->where('slot_status', SlotStatus::Confirmed)->count());
    }

    public function test_dashboard_and_applicants_pages_show_export_and_refresh_actions(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Export Confirmed Applicants')
            ->assertSee('Refresh Confirmed Slots');

        $this->actingAs($admin)
            ->get(route('admin.applicants.index'))
            ->assertOk()
            ->assertSee('Export Confirmed Applicants')
            ->assertSee('Refresh Confirmed Slots');
    }

    private function sheetXml(string $binary): string
    {
        $path = tempnam(sys_get_temp_dir(), 'xlsx');
        file_put_contents($path, $binary);

        $zip = new ZipArchive;
        $this->assertTrue($zip->open($path) === true);
        $xml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();
        @unlink($path);

        $this->assertNotFalse($xml);

        return $xml;
    }
}
