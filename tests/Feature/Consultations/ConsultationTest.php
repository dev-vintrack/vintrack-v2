<?php

namespace Tests\Feature\Consultations;

use App\Application\Consultas\Services\ConsultationService;
use App\Application\Credits\CommandHandlers\DebitCreditsCommandHandler;
use App\Application\Vehicles\Services\VehicleUpserter;
use App\Domain\Consultas\Services\ProviderAdapterInterface;
use App\Domain\Consultas\Services\ProviderAdapterRegistry;
use App\Domain\Consultas\ValueObjects\ConsultationRequest;
use App\Domain\Consultas\ValueObjects\ConsultationResponse;
use App\Infrastructure\Persistence\Models\Provider;
use App\Infrastructure\Persistence\Models\ProviderService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ConsultationTest extends TestCase
{
    use RefreshDatabase;

    public function test_consultation_fails_when_user_has_no_credits(): void
    {
        $user = User::factory()->create([
            'password' => 'password123',
            'activo' => true,
            'approved_at' => now(),
        ]);

        $provider = Provider::create([
            'code' => 'PLACAS',
            'adapter_code' => 'placas',
            'name' => 'Placas.info',
            'base_url' => 'https://placas.info/api/v2/consultar/',
            'policies_json' => ['creditCost' => 1.0],
            'enabled' => true,
        ]);

        $service = ProviderService::create([
            'provider_id' => $provider->id,
            'key' => 'Placas_Service',
            'service_code' => 'placas_service',
            'name' => 'Placas Service',
            'credit_cost' => 1,
            'available_credits' => 0,
            'enabled' => true,
        ]);

        $this->mockAdapter();

        $this->actingAs($user)
            ->postJson(route('consult'), [
                'provider_id' => $provider->id,
                'type' => 'placa',
                'value' => 'ABC1234',
                'services' => ['placas_service'],
            ])
            ->assertStatus(402)
            ->assertJson(['success' => false, 'message' => 'Saldo insuficiente de créditos.']);
    }

    public function test_consultation_succeeds_with_credits(): void
    {
        $user = User::factory()->create([
            'password' => 'password123',
            'rol' => 'admin',
            'activo' => true,
            'approved_at' => now(),
        ]);

        $provider = Provider::create([
            'code' => 'PLACAS',
            'adapter_code' => 'placas',
            'name' => 'Placas.info',
            'base_url' => 'https://placas.info/api/v2/consultar/',
            'policies_json' => ['creditCost' => 1.0],
            'enabled' => true,
        ]);

        $service = ProviderService::create([
            'provider_id' => $provider->id,
            'key' => 'Placas_Service',
            'service_code' => 'placas_service',
            'name' => 'Placas Service',
            'credit_cost' => 1,
            'available_credits' => 10,
            'enabled' => true,
        ]);

        $this->mockAdapter();

        $this->actingAs($user)
            ->postJson(route('admin.credits.purchase.store'), [
                'user_id' => $user->id,
                'provider_service_id' => $service->id,
                'amount' => 10,
                'validity_days' => 30,
                'reason' => 'Test credits',
            ]);

        $response = $this->actingAs($user)
            ->postJson(route('consult'), [
                'provider_id' => $provider->id,
                'type' => 'placa',
                'value' => 'ABC1234',
                'services' => ['placas_service'],
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $walletRepo = app(\App\Domain\Credits\Repositories\WalletRepositoryInterface::class);
        $wallet = $walletRepo->findByUserAndService($user->id, $service->id);
        $this->assertEquals(9.0, $wallet->balance()->amount());
    }

    /**
     * Regresion: cuando el proveedor externo falla y reporta un http status que Laravel
     * vacia automaticamente (204/304) o que es invalido (0, >599), el endpoint /consult
     * debia devolver ese status crudo, produciendo un body JSON vacio y rompiendo el
     * frontend con "Unexpected end of JSON input". El controlador debe normalizar el
     * status de salida sin perder el status real (expuesto en el campo "status").
     */
    #[DataProvider('unsafeUpstreamHttpStatusProvider')]
    public function test_consultation_failure_with_unsafe_upstream_status_returns_valid_json(int $upstreamStatus): void
    {
        $user = User::factory()->create([
            'password' => 'password123',
            'rol' => 'admin',
            'activo' => true,
            'approved_at' => now(),
        ]);

        $provider = Provider::create([
            'code' => 'PLACAS',
            'adapter_code' => 'placas',
            'name' => 'Placas.info',
            'base_url' => 'https://placas.info/api/v2/consultar/',
            'policies_json' => ['creditCost' => 1.0],
            'enabled' => true,
        ]);

        $service = ProviderService::create([
            'provider_id' => $provider->id,
            'key' => 'Placas_Service',
            'service_code' => 'placas_service',
            'name' => 'Placas Service',
            'credit_cost' => 1,
            'available_credits' => 10,
            'enabled' => true,
        ]);

        $this->mockAdapterWithFailure($upstreamStatus);

        $this->actingAs($user)
            ->postJson(route('admin.credits.purchase.store'), [
                'user_id' => $user->id,
                'provider_service_id' => $service->id,
                'amount' => 10,
                'validity_days' => 30,
                'reason' => 'Test credits',
            ]);

        $response = $this->actingAs($user)
            ->postJson(route('consult'), [
                'provider_id' => $provider->id,
                'type' => 'placa',
                'value' => 'ABC1234',
                'services' => ['placas_service'],
            ]);

        // El status HTTP de la respuesta nunca debe ser uno que Laravel vacie (204/304)
        // ni uno invalido (fuera de 100-599).
        $this->assertNotContains($response->getStatusCode(), [204, 304]);
        $this->assertGreaterThanOrEqual(100, $response->getStatusCode());
        $this->assertLessThanOrEqual(599, $response->getStatusCode());

        // El body debe seguir siendo JSON valido y no vacio.
        $response->assertJson([
            'success' => false,
            'status' => $upstreamStatus,
        ]);
    }

    public static function unsafeUpstreamHttpStatusProvider(): array
    {
        return [
            'connection failure (0)' => [0],
            'no content (204)' => [204],
            'not modified (304)' => [304],
        ];
    }

    private function mockAdapter(): void
    {
        $fakeAdapter = new class implements ProviderAdapterInterface {
            public function supports(string $adapterCode): bool
            {
                return strtolower($adapterCode) === 'placas';
            }

            public function consult(ConsultationRequest $request): ConsultationResponse
            {
                return new ConsultationResponse(
                    true,
                    200,
                    null,
                    ['repuve' => []],
                    'fake-api-id',
                    [
                        'repuve_robo' => 0,
                        'pgj_robo' => 0,
                        'ocra_robo' => 0,
                        'carfax_robo' => 0,
                        'rapi_robo' => 0,
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
                $app->make(\App\Domain\Consultas\Repositories\ConsultationRepositoryInterface::class),
                $app->make(\App\Application\Consultas\Notifications\ConsultationNotifierInterface::class),
                $app->make(VehicleUpserter::class)
            );
        });
    }

    private function mockAdapterWithFailure(int $upstreamStatus): void
    {
        $fakeAdapter = new class($upstreamStatus) implements ProviderAdapterInterface {
            public function __construct(private readonly int $upstreamStatus)
            {
            }

            public function supports(string $adapterCode): bool
            {
                return strtolower($adapterCode) === 'placas';
            }

            public function consult(ConsultationRequest $request): ConsultationResponse
            {
                return new ConsultationResponse(
                    false,
                    $this->upstreamStatus,
                    'Timeout esperando resultado',
                    [],
                    null,
                    [
                        'repuve_robo' => 0,
                        'pgj_robo' => 0,
                        'ocra_robo' => 0,
                        'carfax_robo' => 0,
                        'rapi_robo' => 0,
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
                $app->make(\App\Domain\Consultas\Repositories\ConsultationRepositoryInterface::class),
                $app->make(\App\Application\Consultas\Notifications\ConsultationNotifierInterface::class),
                $app->make(VehicleUpserter::class)
            );
        });
    }
}
