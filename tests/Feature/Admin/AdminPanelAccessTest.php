<?php

namespace Tests\Feature\Admin;

use App\Infrastructure\Persistence\Models\AdminMenuPermission;
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
    }
}
