<?php

namespace Tests\Feature\Admin;

use App\Infrastructure\Persistence\Models\AdminMenuPermission;
use App\Infrastructure\Persistence\Models\NotificationPolicy;
use App\Infrastructure\Persistence\Models\Provider;
use App\Infrastructure\Persistence\Models\ProviderService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPanelAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\AdminMenuPermissionSeeder::class);
    }

    public function test_admin_can_access_new_admin_views(): void
    {
        $admin = User::factory()->create(['rol' => 'admin']);

        $this->actingAs($admin)
            ->get(route('admin.consultations.index'))
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('admin.wallets.index'))
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('admin.wallets.movements'))
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('admin.packages.active'))
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('admin.menu-permissions.index'))
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('admin.providers.index'))
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('admin.notifications.index'))
            ->assertOk()
            ->assertSee('Políticas por servicio')
            ->assertSee('Historial de entregas');
    }

    public function test_admin_can_update_a_provider_service_notification_policy(): void
    {
        $admin = User::factory()->create(['rol' => 'admin']);
        $provider = Provider::create([
            'code' => 'NOTIFY-TEST',
            'adapter_code' => 'notify-test',
            'name' => 'Proveedor de prueba',
            'base_url' => 'https://example.test',
            'enabled' => true,
        ]);
        $service = ProviderService::create([
            'provider_id' => $provider->id,
            'key' => 'NOTIFY-SERVICE',
            'service_code' => 'notify-service',
            'name' => 'Servicio de prueba',
            'credit_cost' => 1,
            'available_credits' => 100,
            'enabled' => true,
        ]);
        $policy = NotificationPolicy::resolveFor($service, NotificationPolicy::LOW_BALANCE);

        $this->actingAs($admin)
            ->put(route('admin.notifications.update', $policy), [
                'enabled' => 1,
                'low_balance_threshold' => 12,
                'cooldown_hours' => 48,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('notification_policies', [
            'id' => $policy->id,
            'enabled' => true,
            'low_balance_threshold' => 12,
            'cooldown_hours' => 48,
        ]);
    }

    public function test_soporte_can_access_new_admin_views_except_permissions(): void
    {
        $soporte = User::factory()->create(['rol' => 'soporte']);

        $this->actingAs($soporte)
            ->get(route('admin.consultations.index'))
            ->assertOk();

        $this->actingAs($soporte)
            ->get(route('admin.menu-permissions.index'))
            ->assertForbidden();

        $this->actingAs($soporte)
            ->get(route('admin.notifications.index'))
            ->assertForbidden();
    }

    public function test_analista_cannot_access_new_admin_views(): void
    {
        $analista = User::factory()->create(['rol' => 'analista']);

        $this->actingAs($analista)
            ->get(route('admin.consultations.index'))
            ->assertForbidden();
    }

    public function test_customer_cannot_access_admin_views(): void
    {
        $customer = User::factory()->create(['rol' => 'cliente_registrado']);

        $this->actingAs($customer)
            ->get(route('admin.consultations.index'))
            ->assertForbidden();

        $this->actingAs($customer)
            ->get(route('admin.notifications.index'))
            ->assertForbidden();
    }
}
