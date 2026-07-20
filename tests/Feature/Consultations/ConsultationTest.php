<?php

namespace Tests\Feature\Consultations;

use App\Application\Consultas\Services\ConsultationService;
use App\Application\Credits\CommandHandlers\DebitCreditsCommandHandler;
use App\Domain\Consultas\Services\ProviderAdapterInterface;
use App\Domain\Consultas\Services\ProviderAdapterRegistry;
use App\Domain\Consultas\ValueObjects\ConsultationRequest;
use App\Domain\Consultas\ValueObjects\ConsultationResponse;
use App\Infrastructure\Persistence\Models\Provider;
use App\Infrastructure\Persistence\Models\ProviderService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
            'name' => 'Placas.info',
            'base_url' => 'https://placas.info/api/v2/consultar/',
            'policies_json' => ['creditCost' => 1.0],
            'enabled' => true,
        ]);

        $service = ProviderService::create([
            'provider_id' => $provider->id,
            'key' => 'Placas_Service',
            'name' => 'Placas Service',
            'credit_cost' => 1,
            'available_credits' => 0,
            'enabled' => true,
        ]);

        $this->mockAdapter();

        $this->actingAs($user)
            ->postJson(route('consult'), [
                'provider' => 'PLACAS',
                'type' => 'placa',
                'value' => 'ABC1234',
                'services' => ['Placas_Service'],
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
            'name' => 'Placas.info',
            'base_url' => 'https://placas.info/api/v2/consultar/',
            'policies_json' => ['creditCost' => 1.0],
            'enabled' => true,
        ]);

        $service = ProviderService::create([
            'provider_id' => $provider->id,
            'key' => 'Placas_Service',
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
                'amount' => 5,
                'reason' => 'Test credits',
            ]);

        $response = $this->actingAs($user)
            ->postJson(route('consult'), [
                'provider' => 'PLACAS',
                'type' => 'placa',
                'value' => 'ABC1234',
                'services' => ['Placas_Service'],
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $walletRepo = app(\App\Domain\Credits\Repositories\WalletRepositoryInterface::class);
        $wallet = $walletRepo->findByUserAndService($user->id, $service->id);
        $this->assertEquals(4.0, $wallet->balance()->amount());
    }

    private function mockAdapter(): void
    {
        $fakeAdapter = new class implements ProviderAdapterInterface {
            public function supports(string $providerCode): bool
            {
                return strtoupper($providerCode) === 'PLACAS';
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
                $app->make(\App\Application\Consultas\Notifications\ConsultationNotifierInterface::class)
            );
        });
    }
}
