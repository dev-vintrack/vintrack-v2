<?php

namespace Tests\Feature\NotificationCases;

use App\Application\NotificationCases\Services\NotificationCaseAuditService;
use App\Application\NotificationCases\Services\NotificationCaseDocumentService;
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
use RuntimeException;
use Tests\TestCase;

class NotificationCaseDocumentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    public function test_owner_uploads_lists_downloads_and_logically_removes_private_pdf(): void
    {
        [$owner, $case] = $this->case();
        $response = $this->actingAs($owner)->withHeaders(['Idempotency-Key' => 'upload-1', 'User-Agent' => 'Sprint03Test'])
            ->post(route('notification-cases.documents.store', $case), ['document' => $this->pdf('evidencia.pdf')]);
        $response->assertCreated();
        $document = NotificationCaseDocument::firstOrFail();
        Storage::disk('local')->assertExists($document->storage_key);
        $this->assertSame(hash('sha256', $this->pdfBytes()), $document->sha256);
        $this->assertStringNotContainsString('evidencia', $document->storage_key);

        $this->actingAs($owner)->get(route('notification-cases.documents.index', $case))
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonMissing(['storage_key' => $document->storage_key]);
        $this->actingAs($owner)->get(route('notification-cases.documents.show', [$case, $document]))->assertStatus(423);
        $document->forceFill(['malware_scan_status' => MalwareScanStatus::CLEAN, 'malware_scanned_at' => now()])->save();
        $this->actingAs($owner)->get(route('notification-cases.documents.show', [$case, $document]))
            ->assertOk()->assertHeader('x-content-type-options', 'nosniff');

        $this->actingAs($owner)->withHeader('Idempotency-Key', 'remove-1')->delete(route('notification-cases.documents.destroy', [$case, $document]))->assertOk();
        $this->actingAs($owner)->withHeader('Idempotency-Key', 'remove-1')->delete(route('notification-cases.documents.destroy', [$case, $document]))->assertOk();
        $this->assertNotNull($document->fresh()->removed_at);
        Storage::disk('local')->assertExists($document->storage_key);
        $this->assertDatabaseCount('notification_case_events', 2);
        $this->actingAs($owner)->get(route('notification-cases.documents.index', $case))->assertJsonCount(0, 'data');
        $this->actingAs($owner)->get(route('notification-cases.documents.show', [$case, $document]))->assertNotFound();
    }

    public function test_upload_is_idempotent_and_active_limit_excludes_removed_documents(): void
    {
        [$owner, $case] = $this->case();
        $service = app(NotificationCaseDocumentService::class);
        $first = $service->upload($case, $owner, $this->pdf('same.pdf'), 'same-request', 'c1');
        $retry = $service->upload($case, $owner, $this->pdf('same.pdf'), 'same-request', 'c1');
        $this->assertSame($first->id, $retry->id);

        for ($i = 1; $i < 8; $i++) {
            $service->upload($case, $owner, $this->pdf("doc-$i.pdf"), "request-$i", "c-$i");
        }
        $this->assertDatabaseCount('notification_case_documents', 8);
        $this->actingAs($owner)->withHeader('Idempotency-Key', 'ninth')->post(route('notification-cases.documents.store', $case), ['document' => $this->pdf('ninth.pdf')])->assertConflict();

        $service->remove($case, $first, $owner, 'remove-first', 'remove-c');
        $service->upload($case, $owner, $this->pdf('replacement.pdf'), 'replacement', 'replacement-c');
        $this->assertSame(8, NotificationCaseDocument::where('notification_case_id', $case->id)->whereNull('removed_at')->count());
    }

    public function test_cross_client_and_cross_case_idor_are_rejected(): void
    {
        [$owner, $case] = $this->case();
        [$other, $otherCase] = $this->case('oficial');
        $document = app(NotificationCaseDocumentService::class)->upload($case, $owner, $this->pdf('private.pdf'), 'owner-upload', 'owner-c');

        $this->actingAs($other)->get(route('notification-cases.documents.index', $case))->assertForbidden();
        $this->actingAs($other)->get(route('notification-cases.documents.show', [$case, $document]))->assertNotFound();
        $this->actingAs($other)->withHeader('Idempotency-Key', 'attack')->post(route('notification-cases.documents.store', $case), ['document' => $this->pdf('attack.pdf')])->assertForbidden();
        $this->actingAs($other)->withHeader('Idempotency-Key', 'attack-remove')->delete(route('notification-cases.documents.destroy', [$case, $document]))->assertForbidden();
        $this->actingAs($owner)->get(route('notification-cases.documents.show', [$otherCase, $document]))->assertNotFound();
    }

    public function test_real_mime_extension_empty_and_spoofing_are_rejected(): void
    {
        [$owner, $case] = $this->case();
        foreach ([
            UploadedFile::fake()->createWithContent('empty.pdf', ''),
            UploadedFile::fake()->createWithContent('../../evil.php', '<?php echo 1;'),
            UploadedFile::fake()->createWithContent('double.pdf.exe', $this->pdfBytes()),
            UploadedFile::fake()->createWithContent('fake.jpg', $this->pdfBytes()),
        ] as $index => $file) {
            $this->actingAs($owner)->withHeader('Idempotency-Key', 'invalid-'.$index)
                ->post(route('notification-cases.documents.store', $case), ['document' => $file])->assertUnprocessable();
        }
        $this->assertDatabaseCount('notification_case_documents', 0);
    }

    public function test_pdf_jpg_jpeg_png_and_exact_size_boundary_are_enforced(): void
    {
        [$owner, $case] = $this->case();
        $service = app(NotificationCaseDocumentService::class);
        foreach (['photo.jpg', 'photo.jpeg', 'image.png'] as $index => $name) {
            $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            $format = $extension === 'png' ? 'png' : 'jpeg';
            $service->upload($case, $owner, UploadedFile::fake()->image($name, 2, 2)->mimeType('image/'.$format), 'image-'.$index, 'image-c-'.$index);
        }

        $exact = "%PDF-1.4\n".str_repeat('A', 3145728 - 15)."\n%%EOF";
        $this->assertSame(3145728, strlen($exact));
        $service->upload($case, $owner, UploadedFile::fake()->createWithContent('limit.PDF', $exact), 'exact-limit', 'exact-c');

        $this->actingAs($owner)->withHeader('Idempotency-Key', 'over-limit')
            ->post(route('notification-cases.documents.store', $case), ['document' => UploadedFile::fake()->createWithContent('over.pdf', $exact.'X')])
            ->assertUnprocessable();
    }

    public function test_audit_failure_rolls_back_metadata_and_compensates_stored_file(): void
    {
        [$owner, $case] = $this->case();
        $audit = \Mockery::mock(NotificationCaseAuditService::class);
        $audit->shouldReceive('record')->once()->andThrow(new RuntimeException('fault injection'));
        $this->app->instance(NotificationCaseAuditService::class, $audit);
        $this->app->forgetInstance(NotificationCaseDocumentService::class);

        try {
            app(NotificationCaseDocumentService::class)->upload($case, $owner, $this->pdf('fault.pdf'), 'fault-request', 'fault-c');
            $this->fail('Expected injected audit failure.');
        } catch (RuntimeException $exception) {
            $this->assertSame('fault injection', $exception->getMessage());
        }

        $this->assertDatabaseCount('notification_case_documents', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    private function case(string $role = 'oficial'): array
    {
        $owner = User::factory()->create(['rol' => $role, 'activo' => true, 'approved_at' => now()]);
        $providerCode = 'P'.uniqid();
        $provider = Provider::create(['code' => $providerCode, 'adapter_code' => strtolower($providerCode), 'name' => 'Provider', 'base_url' => 'https://example.test', 'enabled' => true]);
        $serviceCode = strtolower($providerCode);
        $service = ProviderService::create(['provider_id' => $provider->id, 'key' => strtoupper($providerCode), 'service_code' => $serviceCode, 'name' => 'VHR', 'credit_cost' => 1, 'available_credits' => 100, 'enabled' => true]);
        $consultation = Consultation::create(['user_id' => $owner->id, 'provider_id' => $provider->id, 'provider_service_id' => $service->id, 'criterio' => 'vin', 'valor' => '1HGCM82633A'.str_pad((string) $owner->id, 6, '0', STR_PAD_LEFT), 'services' => [$serviceCode], 'success' => true, 'alerta_robo' => true]);
        $case = new NotificationCase;
        $case->forceFill(['consultation_id' => $consultation->id, 'user_id' => $owner->id, 'case_number' => 'NT-2026-'.str_pad((string) $consultation->id, 6, '0', STR_PAD_LEFT), 'vin' => $consultation->valor, 'vin_key' => $consultation->valor, 'status' => NotificationCaseStatus::PENDING, 'notification_deadline_at' => now()->addDays(3)->endOfDay(), 'opened_at' => now(), 'auto_close_at' => now()->addDays(30), 'lock_version' => 0, 'creation_key' => 'test:'.$consultation->id])->save();

        return [$owner, $case];
    }

    private function pdf(string $name): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, $this->pdfBytes());
    }

    private function pdfBytes(): string
    {
        return "%PDF-1.4\n1 0 obj<</Type/Catalog>>endobj\n%%EOF";
    }
}
