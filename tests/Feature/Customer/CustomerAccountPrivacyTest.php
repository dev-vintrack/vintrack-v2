<?php

namespace Tests\Feature\Customer;

use App\Infrastructure\Persistence\Models\Consultation;
use App\Infrastructure\Persistence\Models\Provider;
use App\Infrastructure\Persistence\Models\ProviderService;
use App\Infrastructure\Persistence\Models\UserProviderWallet;
use App\Infrastructure\Persistence\Models\WalletLedgerEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerAccountPrivacyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\CustomerMenuPermissionSeeder::class);
    }

    public function test_customer_pages_only_show_authenticated_users_records(): void
    {
        [$provider, $service] = $this->createService();
        $customer = $this->createUser('cliente_registrado');
        $otherCustomer = $this->createUser('cliente_registrado');

        $ownWallet = $this->createWallet($customer, $provider, $service, 120);
        $otherWallet = $this->createWallet($otherCustomer, $provider, $service, 980);

        WalletLedgerEntry::create([
            'wallet_id' => $ownWallet->id,
            'provider_service_id' => $service->id,
            'delta' => 120,
            'reason' => 'MOVIMIENTO-PROPIO',
            'correlation_id' => 'customer-own-movement',
        ]);
        WalletLedgerEntry::create([
            'wallet_id' => $otherWallet->id,
            'provider_service_id' => $service->id,
            'delta' => 980,
            'reason' => 'MOVIMIENTO-AJENO',
            'correlation_id' => 'customer-other-movement',
        ]);

        $this->createConsultation($customer, $provider, 'VIN-PROPIO');
        $this->createConsultation($otherCustomer, $provider, 'VIN-AJENO');

        $this->actingAs($customer)
            ->get(route('customer.credits'))
            ->assertOk()
            ->assertSee('120.00')
            ->assertDontSee('980.00')
            ->assertSee(route('customer.credits'), false)
            ->assertSee(route('customer.movements'), false)
            ->assertSee(route('customer.consultations'), false)
            ->assertSee('customerCreditsTable')
            ->assertSee('excelHtml5')
            ->assertSee('pdfHtml5');

        $this->actingAs($customer)
            ->get(route('customer.movements'))
            ->assertOk()
            ->assertSee('MOVIMIENTO-PROPIO')
            ->assertDontSee('MOVIMIENTO-AJENO')
            ->assertSee('customerMovementsTable')
            ->assertSee('excelHtml5')
            ->assertSee('pdfHtml5');

        $this->actingAs($customer)
            ->get(route('customer.consultations'))
            ->assertOk()
            ->assertSee('VIN-PROPIO')
            ->assertDontSee('VIN-AJENO')
            ->assertSee('customerConsultationsTable')
            ->assertSee('excelHtml5')
            ->assertSee('pdfHtml5');
    }

    public function test_customer_routes_reject_user_id_manipulation(): void
    {
        $customer = $this->createUser('cliente_registrado');
        $otherCustomer = $this->createUser('cliente_registrado');

        foreach (['customer.credits', 'customer.movements', 'customer.consultations'] as $routeName) {
            $this->actingAs($customer)
                ->get(route($routeName, ['user_id' => $otherCustomer->id]))
                ->assertBadRequest();
        }
    }

    public function test_only_supported_active_customer_roles_can_access_customer_portal(): void
    {
        foreach (['cliente_registrado', 'perito', 'oficial', 'unidad_analisis'] as $role) {
            $this->actingAs($this->createUser($role))
                ->get(route('customer.credits'))
                ->assertOk();
        }

        $this->actingAs($this->createUser('admin'))
            ->get(route('customer.credits'))
            ->assertForbidden();

        $this->actingAs($this->createUser('ocasional'))
            ->get(route('customer.credits'))
            ->assertForbidden();

        $this->actingAs($this->createUser('cliente_registrado', activo: false))
            ->get(route('customer.credits'))
            ->assertForbidden();
    }

    public function test_pending_approval_customer_is_redirected_to_pending_home(): void
    {
        $customer = $this->createUser('perito', status: 'pending');

        $this->actingAs($customer)
            ->get(route('customer.credits'))
            ->assertRedirect(route('home.pending'));
    }

    public function test_customer_cannot_open_another_users_report(): void
    {
        [$provider] = $this->createService();
        $customer = $this->createUser('cliente_registrado');
        $otherCustomer = $this->createUser('cliente_registrado');
        $otherConsultation = $this->createConsultation($otherCustomer, $provider, 'VIN-PRIVADO');

        $this->actingAs($customer)
            ->get(route('reports.show', $otherConsultation->id))
            ->assertNotFound();
    }

    public function test_admin_can_open_any_user_report(): void
    {
        [$provider] = $this->createService();
        $admin = $this->createUser('admin');
        $customer = $this->createUser('cliente_registrado');
        $customerConsultation = $this->createConsultation($customer, $provider, 'VIN-CLIENTE');

        $this->actingAs($admin)
            ->get(route('reports.show', $customerConsultation->id))
            ->assertOk()
            ->assertSee('VIN-CLIENTE');
    }

    private function createUser(string $role, bool $activo = true, string $status = 'active'): User
    {
        return User::factory()->create([
            'rol' => $role,
            'activo' => $activo,
            'status' => $status,
            'approved_at' => $status === 'active' ? now() : null,
        ]);
    }

    private function createService(): array
    {
        $provider = Provider::create([
            'code' => 'TEST-' . fake()->unique()->numerify('#####'),
            'adapter_code' => 'test-' . fake()->unique()->numerify('#####'),
            'name' => 'Proveedor de prueba',
            'base_url' => 'https://example.test',
            'enabled' => true,
        ]);

        $service = ProviderService::create([
            'provider_id' => $provider->id,
            'key' => 'SERVICE-' . fake()->unique()->numerify('#####'),
            'service_code' => 'service-' . fake()->unique()->numerify('#####'),
            'name' => 'Servicio de prueba',
            'credit_cost' => 1,
            'available_credits' => 1000,
            'enabled' => true,
        ]);

        return [$provider, $service];
    }

    private function createWallet(User $user, Provider $provider, ProviderService $service, float $balance): UserProviderWallet
    {
        return UserProviderWallet::create([
            'user_id' => $user->id,
            'provider_id' => $provider->id,
            'provider_service_id' => $service->id,
            'balance' => $balance,
            'min_alert' => 10,
            'validity_start' => now(),
            'validity_end' => now()->addDays(30),
            'status' => 'active',
        ]);
    }

    private function createConsultation(User $user, Provider $provider, string $value): Consultation
    {
        return Consultation::create([
            'user_id' => $user->id,
            'provider_id' => $provider->id,
            'criterio' => 'vin',
            'valor' => $value,
            'services' => ['Servicio de prueba'],
            'costo_credito' => 1,
            'success' => true,
            'alerta_robo' => false,
            'response_json' => [],
        ]);
    }
}
