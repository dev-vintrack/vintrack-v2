<?php

namespace Tests\Feature\Customer;

use App\Infrastructure\Persistence\Models\CustomerMenuPermission;
use App\Infrastructure\Persistence\Models\Provider;
use App\Infrastructure\Persistence\Models\ProviderService;
use App\Infrastructure\Persistence\Models\UserProviderWallet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerMenuPermissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\CustomerMenuPermissionSeeder::class);
    }

    public function test_customer_sees_default_menu_items(): void
    {
        $customer = $this->createUser('cliente_registrado');

        $this->actingAs($customer)
            ->get(route('customer.credits'))
            ->assertOk();

        $this->actingAs($customer)
            ->get(route('customer.movements'))
            ->assertOk();

        $this->actingAs($customer)
            ->get(route('customer.consultations'))
            ->assertOk();
    }

    public function test_disabled_customer_menu_returns_forbidden(): void
    {
        $customer = $this->createUser('cliente_registrado');

        CustomerMenuPermission::forRole($customer->id_rol)
            ->where('route_name', 'customer.credits')
            ->update(['enabled' => false]);

        $this->actingAs($customer)
            ->get(route('customer.credits'))
            ->assertForbidden();

        $this->actingAs($customer)
            ->get(route('customer.movements'))
            ->assertOk();
    }

    public function test_admin_can_manage_customer_menu_permissions(): void
    {
        $admin = User::factory()->create(['rol' => 'admin']);

        $this->actingAs($admin)
            ->get(route('admin.customer-menu-permissions.index'))
            ->assertOk()
            ->assertSee('Permisos de Menú Cliente');
    }

    public function test_soporte_and_analista_can_manage_customer_menu_permissions(): void
    {
        foreach (['soporte', 'analista'] as $role) {
            $user = User::factory()->create(['rol' => $role]);

            $this->actingAs($user)
                ->get(route('admin.customer-menu-permissions.index'))
                ->assertOk();
        }
    }

    public function test_customer_cannot_access_admin_customer_menu_permissions(): void
    {
        $customer = $this->createUser('cliente_registrado');

        $this->actingAs($customer)
            ->get(route('admin.customer-menu-permissions.index'))
            ->assertForbidden();
    }

    public function test_admin_can_disable_menu_item_for_role(): void
    {
        $admin = User::factory()->create(['rol' => 'admin']);
        $customer = $this->createUser('cliente_registrado');

        $permission = CustomerMenuPermission::forRole($customer->id_rol)
            ->where('route_name', 'customer.credits')
            ->firstOrFail();

        $this->actingAs($admin)
            ->put(route('admin.customer-menu-permissions.update'), [
                'permissions' => [
                    $permission->id => [
                        'enabled' => 0,
                        'display_order' => 0,
                    ],
                ],
            ])
            ->assertRedirect();

        $this->actingAs($customer)
            ->get(route('customer.credits'))
            ->assertForbidden();
    }

    public function test_ocasional_cannot_access_customer_portal(): void
    {
        $this->actingAs($this->createUser('ocasional'))
            ->get(route('customer.credits'))
            ->assertForbidden();
    }

    public function test_customer_menu_hides_disabled_item(): void
    {
        $customer = $this->createUser('cliente_registrado');

        CustomerMenuPermission::forRole($customer->id_rol)
            ->where('route_name', 'customer.credits')
            ->update(['enabled' => false]);

        $this->actingAs($customer)
            ->get(route('home.cliente'))
            ->assertOk()
            ->assertDontSee('Mis créditos')
            ->assertSee('Mis movimientos')
            ->assertSee('Mis consultas');
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
}
