<?php

namespace Tests\Feature\Consultations;

use App\Application\Consultas\Services\ConsultationService;
use App\Application\Credits\CommandHandlers\DebitCreditsCommandHandler;
use App\Domain\Consultas\Repositories\ConsultationRepositoryInterface;
use App\Domain\Consultas\Services\ProviderAdapterInterface;
use App\Domain\Consultas\Services\ProviderAdapterRegistry;
use App\Domain\Consultas\ValueObjects\ConsultationRequest;
use App\Domain\Consultas\ValueObjects\ConsultationResponse;
use App\Infrastructure\Persistence\Models\Provider;
use App\Infrastructure\Persistence\Models\ProviderService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VinDataConsultationTest extends TestCase
{
    use RefreshDatabase;

    public function test_vindata_consultation_succeeds_with_credits_and_returns_local_report_url(): void
    {
        $user = User::factory()->create([
            'password' => 'password123',
            'rol' => 'admin',
            'activo' => true,
            'approved_at' => now(),
        ]);

        $provider = Provider::create([
            'code' => 'VINDATA',
            'name' => 'VINData',
            'base_url' => 'https://api.vindata.com/v1',
            'policies_json' => ['creditCost' => 1.0],
            'enabled' => true,
        ]);

        $service = ProviderService::create([
            'provider_id' => $provider->id,
            'key' => 'VHR',
            'name' => 'Vehicle History Report',
            'credit_cost' => 1,
            'available_credits' => 10,
            'enabled' => true,
        ]);

        $this->mockAdapter();

        $this->actingAs($user)
            ->postJson(route('admin.credits.purchase.store'), [
                'user_id' => $user->id,
                'provider_service_id' => $service->id,
                'amount' => 5,
                'reason' => 'Test credits',
            ]);

        $response = $this->actingAs($user)
            ->postJson(route('consult'), [
                'provider' => 'VINDATA',
                'type' => 'vin',
                'value' => '1HGCM82633A123456',
                'services' => ['VHR'],
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonPath('local_report_url', fn ($url) => str_contains($url, '/reports/'));

        $walletRepo = app(\App\Domain\Credits\Repositories\WalletRepositoryInterface::class);
        $wallet = $walletRepo->findByUserAndService($user->id, $service->id);
        $this->assertEquals(4.0, $wallet->balance()->amount());
    }

    public function test_report_view_returns_branded_html_for_vindata_consultation(): void
    {
        $user = User::factory()->create([
            'password' => 'password123',
            'rol' => 'admin',
            'activo' => true,
            'approved_at' => now(),
        ]);

        $provider = Provider::create([
            'code' => 'VINDATA',
            'name' => 'VINData',
            'base_url' => 'https://api.vindata.com/v1',
            'policies_json' => ['creditCost' => 1.0],
            'enabled' => true,
        ]);

        $service = ProviderService::create([
            'provider_id' => $provider->id,
            'key' => 'VHR',
            'name' => 'Vehicle History Report',
            'credit_cost' => 1,
            'available_credits' => 10,
            'enabled' => true,
        ]);

        $this->mockAdapter();

        $this->actingAs($user)
            ->postJson(route('admin.credits.purchase.store'), [
                'user_id' => $user->id,
                'provider_service_id' => $service->id,
                'amount' => 5,
                'reason' => 'Test credits',
            ]);

        $consultResponse = $this->actingAs($user)
            ->postJson(route('consult'), [
                'provider' => 'VINDATA',
                'type' => 'vin',
                'value' => '1HGCM82633A123456',
                'services' => ['VHR'],
            ]);

        $consultationId = (int) basename($consultResponse->json('local_report_url'));

        $this->actingAs($user)
            ->get(route('reports.show', $consultationId))
            ->assertStatus(200)
            ->assertSee('VINTrack')
            ->assertSee('1HGCM82633A123456');
    }

    public function test_pdf_report_returns_pdf_response_for_vindata_consultation(): void
    {
        $user = User::factory()->create([
            'password' => 'password123',
            'rol' => 'admin',
            'activo' => true,
            'approved_at' => now(),
        ]);

        $provider = Provider::create([
            'code' => 'VINDATA',
            'name' => 'VINData',
            'base_url' => 'https://api.vindata.com/v1',
            'policies_json' => ['creditCost' => 1.0],
            'enabled' => true,
        ]);

        $service = ProviderService::create([
            'provider_id' => $provider->id,
            'key' => 'VHR',
            'name' => 'Vehicle History Report',
            'credit_cost' => 1,
            'available_credits' => 10,
            'enabled' => true,
        ]);

        $this->mockAdapter();

        $this->actingAs($user)
            ->postJson(route('admin.credits.purchase.store'), [
                'user_id' => $user->id,
                'provider_service_id' => $service->id,
                'amount' => 5,
                'reason' => 'Test credits',
            ]);

        $consultResponse = $this->actingAs($user)
            ->postJson(route('consult'), [
                'provider' => 'VINDATA',
                'type' => 'vin',
                'value' => '1HGCM82633A123456',
                'services' => ['VHR'],
            ]);

        $consultationId = (int) basename($consultResponse->json('local_report_url'));

        $this->actingAs($user)
            ->get(route('reports.pdf', $consultationId))
            ->assertStatus(200)
            ->assertHeader('Content-Type', 'application/pdf');
    }

    private function mockAdapter(): void
    {
        $fakeAdapter = new class implements ProviderAdapterInterface {
            public function supports(string $providerCode): bool
            {
                return strtoupper($providerCode) === 'VINDATA';
            }

            public function consult(ConsultationRequest $request): ConsultationResponse
            {
                return new ConsultationResponse(
                    true,
                    200,
                    null,
                    [
                        'uuid' => 'fake-uuid',
                        'productCode' => 'VHR',
                        'productName' => 'Vehicle History Report',
                        'vin' => $request->value(),
                        'rawData' => [
                            'summary' => [
                                'year' => '2020',
                                'make' => 'Honda',
                                'model' => 'Accord',
                            ],
                            'reportSummary' => [
                                'message' => 'No se encontraron problemas mayores.',
                                'color' => 'green',
                            ],
                        ],
                    ],
                    'fake-uuid',
                    [
                        'active_theft' => 0,
                        'open_lien' => 0,
                        'junk_salvage' => 0,
                        'odometer_issue' => 0,
                    ]
                );
            }
        };

        app()->singleton(ProviderAdapterRegistry::class, function () use ($fakeAdapter) {
            $registry = new ProviderAdapterRegistry();
            $registry->register($fakeAdapter);

            return $registry;
        });

        app()->singleton(ConsultationService::class, function ($app) {
            return new ConsultationService(
                $app->make(\App\Domain\Providers\Repositories\ProviderRepositoryInterface::class),
                $app->make(\App\Domain\Providers\Repositories\ProviderServiceRepositoryInterface::class),
                $app->make(ProviderAdapterRegistry::class),
                $app->make(\App\Domain\Credits\Repositories\WalletRepositoryInterface::class),
                $app->make(DebitCreditsCommandHandler::class),
                $app->make(ConsultationRepositoryInterface::class),
                $app->make(\App\Application\Consultas\Notifications\ConsultationNotifierInterface::class)
            );
        });
    }
}
