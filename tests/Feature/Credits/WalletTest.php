<?php

namespace Tests\Feature\Credits;

use App\Domain\Credits\Repositories\WalletRepositoryInterface;
use App\Infrastructure\Persistence\Models\Provider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalletTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_receive_credits_and_wallet_is_created(): void
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

        $service = \App\Infrastructure\Persistence\Models\ProviderService::create([
            'provider_id' => $provider->id,
            'key' => 'Placas_Service',
            'name' => 'Placas Service',
            'credit_cost' => 1,
            'available_credits' => 20,
            'enabled' => true,
        ]);

        $this->actingAs($user)
            ->postJson(route('admin.credits.purchase.store'), [
                'user_id' => $user->id,
                'provider_service_id' => $service->id,
                'amount' => 10,
                'validity_days' => 30,
                'reason' => 'Test credit purchase',
            ])
            ->assertRedirect();

        $walletRepo = app(WalletRepositoryInterface::class);
        $wallet = $walletRepo->findByUserAndService($user->id, $service->id);

        $this->assertNotNull($wallet);
        $this->assertEquals(10.0, $wallet->balance()->amount());
    }
}
