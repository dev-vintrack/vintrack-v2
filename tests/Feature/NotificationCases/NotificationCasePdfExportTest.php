<?php

namespace Tests\Feature\NotificationCases;

use App\Domain\NotificationCases\Enums\MalwareScanStatus;
use App\Domain\NotificationCases\Enums\NotificationCaseStatus;
use App\Infrastructure\Persistence\Models\Consultation;
use App\Infrastructure\Persistence\Models\NotificationCase;
use App\Infrastructure\Persistence\Models\NotificationCaseDocument;
use App\Infrastructure\Persistence\Models\Provider;
use App\Infrastructure\Persistence\Models\ProviderService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class NotificationCasePdfExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    public function test_only_an_administrator_can_download_a_validated_case_pdf_without_mutating_the_case(): void
    {
        [$owner, $case] = $this->case(NotificationCaseStatus::VALIDATED);
        $analyst = User::factory()->create(['rol' => 'analista', 'activo' => true]);
        foreach (range(1, 3) as $number) {
            $image = UploadedFile::fake()->image("evidencia-{$number}.jpg", 100, 100);
            $key = "exports/evidencia-{$number}.jpg";
            Storage::disk('local')->put($key, file_get_contents($image->getRealPath()));
            NotificationCaseDocument::forceCreate(['notification_case_id' => $case->id, 'uploaded_by_user_id' => $owner->id, 'original_name' => "evidencia-{$number}.jpg", 'storage_disk' => 'local', 'storage_key' => $key, 'mime_type' => 'image/jpeg', 'extension' => 'jpg', 'size_bytes' => Storage::disk('local')->size($key), 'sha256' => hash_file('sha256', $image->getRealPath()), 'malware_scan_status' => MalwareScanStatus::CLEAN]);
        }
        $originalVersion = $case->lock_version;

        $response = $this->actingAs($analyst)->get(route('admin.notification-cases.export-pdf', $case));

        $response->assertOk()->assertHeader('content-type', 'application/pdf')->assertHeader('x-content-type-options', 'nosniff');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
        $this->assertSame($originalVersion, $case->fresh()->lock_version);
        $this->assertSame(NotificationCaseStatus::VALIDATED, $case->fresh()->status);
    }

    public function test_export_is_unavailable_for_non_validated_cases(): void
    {
        [, $case] = $this->case(NotificationCaseStatus::UNDER_REVIEW);
        $analyst = User::factory()->create(['rol' => 'analista', 'activo' => true]);

        $this->actingAs($analyst)->get(route('admin.notification-cases.export-pdf', $case))->assertNotFound();
    }

    private function case(NotificationCaseStatus $status): array
    {
        $owner = User::factory()->create(['rol' => 'oficial', 'activo' => true, 'approved_at' => now()]);
        $provider = Provider::create(['code' => 'P'.uniqid(), 'adapter_code' => 'test', 'name' => 'Provider', 'base_url' => 'https://example.test', 'enabled' => true]);
        $service = ProviderService::create(['provider_id' => $provider->id, 'key' => 'VHR', 'service_code' => 'vhr'.uniqid(), 'name' => 'VHR', 'credit_cost' => 1, 'available_credits' => 100, 'enabled' => true]);
        $consultation = Consultation::create(['user_id' => $owner->id, 'provider_id' => $provider->id, 'provider_service_id' => $service->id, 'criterio' => 'vin', 'valor' => '1HGCM82633A123456', 'services' => ['vhr'], 'success' => true, 'alerta_robo' => true]);
        $case = NotificationCase::forceCreate(['consultation_id' => $consultation->id, 'user_id' => $owner->id, 'case_number' => 'NT-2026-'.str_pad((string) $consultation->id, 6, '0', STR_PAD_LEFT), 'vin' => $consultation->valor, 'vin_key' => $consultation->valor, 'recovery_place' => 'LUGAR', 'country' => 'MEXICO', 'state' => 'PUEBLA', 'municipality' => 'PUEBLA', 'recovered_at' => now()->subHour(), 'license_plate' => 'ABC123', 'make' => 'HONDA', 'model_year' => 2020, 'origin' => 'NACIONAL', 'authority' => 'FISCALIA', 'iph' => 'IPH-1', 'investigation_file' => 'CI-1', 'safekeeping' => 'CORRALON', 'status' => $status, 'notification_deadline_at' => now()->addDays(3)->endOfDay(), 'opened_at' => now(), 'validated_at' => $status === NotificationCaseStatus::VALIDATED ? now() : null, 'auto_close_at' => now()->addDays(30), 'lock_version' => 0, 'creation_key' => 'pdf-export:'.$consultation->id]);

        return [$owner, $case];
    }
}
