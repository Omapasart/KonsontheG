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
        $this->get(route('admin.confirmed-applicants.export', 'beginner'))
            ->assertRedirect(route('admin.login'));
    }

    public function test_export_downloads_only_confirmed_applicants_for_the_chosen_category(): void
    {
        $beginner = Registration::factory()->confirmed()->create([
            'entry_level' => 'beginner',
            'last_name' => 'Santos',
            'first_name' => 'Ana',
            'middle_initial' => 'B',
            'email' => 'ana@example.com',
            'contact_number' => '09170000001',
        ]);
        $otherBeginner = Registration::factory()->confirmed()->create([
            'entry_level' => 'beginner',
            'last_name' => 'Torres',
            'first_name' => 'Ben',
            'email' => 'ben@example.com',
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
            'entry_level' => 'beginner',
            'email' => 'gone@example.com',
        ]);
        Registration::factory()->create([
            'registration_status' => RegistrationStatus::Rejected,
            'entry_level' => 'beginner',
            'email' => 'nope@example.com',
        ]);

        $before = Registration::query()->get(['id', 'updated_at', 'slot_status'])->map->toArray();

        $response = $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.confirmed-applicants.export', 'beginner'));

        $filename = 'KONSONTHEGO_Confirmed_Beginner_Applicants_'.now()->format('Y-m-d').'.xlsx';
        $response->assertOk()->assertDownload($filename);

        $xml = $this->sheetXml($response->streamedContent());

        $this->assertStringContainsString('KONSONTHEGO — Confirmed Beginner Applicants', $xml);
        $this->assertStringContainsString('Export date:', $xml);
        $this->assertStringContainsString('Registration Number', $xml);
        $this->assertStringContainsString('autoFilter', $xml);
        $this->assertStringContainsString('state="frozen"', $xml);
        $this->assertStringContainsString($beginner->registration_number, $xml);
        $this->assertStringContainsString($otherBeginner->registration_number, $xml);
        $this->assertStringContainsString('09170000001', $xml);
        $this->assertStringContainsString('Ana B. Santos', $xml);
        $this->assertStringContainsString('Beginner', $xml);
        $this->assertStringContainsString('ana@example.com', $xml);
        $this->assertStringContainsString('ben@example.com', $xml);
        $this->assertStringNotContainsString('luis@example.com', $xml);
        $this->assertStringNotContainsString('maria@example.com', $xml);
        $this->assertStringNotContainsString($novice->registration_number, $xml);
        $this->assertStringNotContainsString($intermediate->registration_number, $xml);
        $this->assertStringNotContainsString('wait@example.com', $xml);
        $this->assertStringNotContainsString('pending@example.com', $xml);
        $this->assertStringNotContainsString('gone@example.com', $xml);
        $this->assertStringNotContainsString('nope@example.com', $xml);
        $this->assertStringNotContainsString('password', strtolower($xml));
        $this->assertTrue(strpos($xml, 'Santos') < strpos($xml, 'Torres'));

        $noviceXml = $this->sheetXml(
            $this->actingAs(User::factory()->admin()->create())
                ->get(route('admin.confirmed-applicants.export', 'novice'))
                ->streamedContent()
        );
        $this->assertStringContainsString('KONSONTHEGO — Confirmed Novice Applicants', $noviceXml);
        $this->assertStringContainsString('luis@example.com', $noviceXml);
        $this->assertStringNotContainsString('ana@example.com', $noviceXml);

        $this->assertEquals(
            $before,
            Registration::query()->get(['id', 'updated_at', 'slot_status'])->map->toArray()
        );
        $this->assertSame(8, Registration::query()->count());
        $this->assertSame(4, Registration::query()->where('slot_status', SlotStatus::Confirmed)->count());
    }

    public function test_unknown_category_cannot_be_exported(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get('/admin/confirmed-applicants/export/advanced')
            ->assertNotFound();
    }

    public function test_dashboard_and_applicants_pages_show_export_and_refresh_actions(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Export Data')
            ->assertSee('Please wait')
            ->assertSee(route('admin.confirmed-applicants.export', 'beginner'), false)
            ->assertSee(route('admin.confirmed-applicants.export', 'novice'), false)
            ->assertSee(route('admin.confirmed-applicants.export', 'intermediate'), false)
            ->assertDontSee('Export Confirmed Applicants')
            ->assertSee('Refresh Data')
            ->assertSee(route('admin.confirmed-slots.refresh', 'beginner'), false)
            ->assertSee(route('admin.confirmed-slots.refresh', 'novice'), false)
            ->assertSee(route('admin.confirmed-slots.refresh', 'intermediate'), false);

        $this->actingAs($admin)
            ->get(route('admin.applicants.index'))
            ->assertOk()
            ->assertSee('Export Data')
            ->assertSee('Please wait')
            ->assertSee(route('admin.confirmed-applicants.export', 'beginner'), false)
            ->assertSee(route('admin.confirmed-applicants.export', 'novice'), false)
            ->assertSee(route('admin.confirmed-applicants.export', 'intermediate'), false)
            ->assertSee('Refresh Data')
            ->assertSee(route('admin.confirmed-slots.refresh', 'beginner'), false);
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
