<?php

namespace App\Application\Roles\Services;

use App\Infrastructure\Persistence\Models\AdminMenuPermission;
use App\Infrastructure\Persistence\Models\ProviderService;
use App\Infrastructure\Persistence\Models\ProviderServiceRole;
use App\Infrastructure\Persistence\Models\ProviderServiceSection;
use App\Infrastructure\Persistence\Models\ProviderServiceSectionRole;
use App\Models\Role;

class RoleBootstrapService
{
    public function bootstrap(Role $role): void
    {
        $this->createMenuPermissions($role);
        $this->createSectionRoles($role);
        $this->createServiceRoles($role);
    }

    private function createMenuPermissions(Role $role): void
    {
        $order = 0;

        foreach ($this->menuItems() as $item) {
            AdminMenuPermission::firstOrCreate(
                [
                    'id_rol' => $role->id_rol,
                    'route_name' => $item['route'],
                ],
                [
                    'label' => $item['label'],
                    'icon' => $item['icon'],
                    'enabled' => $this->isMenuEnabledByDefault($role, $item['route']),
                    'display_order' => $order++,
                ]
            );
        }
    }

    private function createSectionRoles(Role $role): void
    {
        $sections = ProviderServiceSection::all();

        foreach ($sections as $section) {
            ProviderServiceSectionRole::firstOrCreate(
                [
                    'provider_service_section_id' => $section->id,
                    'id_rol' => $role->id_rol,
                ],
                ['status' => true]
            );
        }
    }

    private function createServiceRoles(Role $role): void
    {
        $services = ProviderService::where('enabled', true)->get();

        foreach ($services as $service) {
            ProviderServiceRole::firstOrCreate(
                [
                    'id_rol' => $role->id_rol,
                    'provider_service_id' => $service->id,
                ],
                ['status' => true]
            );
        }
    }

    /**
     * @return array<int, array{route: string, label: string, icon: string|null}>
     */
    private function menuItems(): array
    {
        return [
            ['route' => 'home',                      'label' => 'Inicio',                 'icon' => 'house-door'],
            ['route' => 'admin.consultations.index', 'label' => 'Historial de Consultas', 'icon' => 'clipboard-data'],
            ['route' => 'admin.inventory.index',     'label' => 'Inventario Global',      'icon' => 'boxes'],
            ['route' => 'admin.purchases.index',     'label' => 'Compras',                'icon' => 'cart'],
            ['route' => 'admin.wallets.movements',   'label' => 'Movimientos Wallet',     'icon' => 'arrow-left-right'],
            ['route' => 'admin.wallets.index',       'label' => 'Créditos por Usuario',   'icon' => 'wallet'],
            ['route' => 'admin.vehicles.index',      'label' => 'Vehículos Registrados',  'icon' => 'car-front'],
            ['route' => 'admin.packages.active',     'label' => 'Paquetes Activos',       'icon' => 'box-seam'],
            ['route' => 'admin.credits.purchase',    'label' => 'Comprar Créditos',       'icon' => 'plus-circle'],
            ['route' => 'admin.packages.index',      'label' => 'Paquetes',               'icon' => 'boxes'],
            ['route' => 'admin.providers.index',     'label' => 'Proveedores',            'icon' => 'hdd-network'],
            ['route' => 'admin.users.index',         'label' => 'Usuarios',               'icon' => 'people'],
            ['route' => 'admin.menu-permissions.index', 'label' => 'Permisos de Menú',    'icon' => 'sliders'],
            ['route' => 'admin.provider-service-roles.index', 'label' => 'Servicios por Rol', 'icon' => 'hand-thumbs-up'],
            ['route' => 'admin.roles.index',         'label' => 'Roles',                  'icon' => 'person-gear'],
            ['route' => 'admin.role-types.index',    'label' => 'Tipos de Rol',           'icon' => 'tags'],
        ];
    }

    private function isMenuEnabledByDefault(Role $role, string $route): bool
    {
        $isAdmin = $role->roleType?->is_admin ?? false;
        $isCustomer = $role->roleType?->is_customer ?? false;

        if ($isAdmin) {
            return true;
        }

        if (! $isCustomer) {
            return false;
        }

        // Clientes no acceden al panel administrativo por defecto.
        return false;
    }
}
