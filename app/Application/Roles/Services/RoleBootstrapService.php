<?php

namespace App\Application\Roles\Services;

use App\Infrastructure\Persistence\Models\AdminMenuPermission;
use App\Infrastructure\Persistence\Models\CustomerMenuPermission;
use App\Infrastructure\Persistence\Models\MenuItem;
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
        $this->createCustomerMenuPermissions($role);
        $this->createSectionRoles($role);
        $this->createServiceRoles($role);
    }

    private function createMenuPermissions(Role $role): void
    {
        foreach (MenuItem::admin()->orderBy('default_display_order')->get() as $item) {
            AdminMenuPermission::firstOrCreate(
                [
                    'id_rol' => $role->id_rol,
                    'route_name' => $item->route_name,
                ],
                [
                    'enabled' => $this->isMenuEnabledByDefault($role, $item->route_name),
                    'display_order' => $item->default_display_order,
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

    private function createCustomerMenuPermissions(Role $role): void
    {
        if (! ($role->roleType?->is_customer ?? false) || $role->nombre === 'ocasional') {
            return;
        }

        foreach (MenuItem::customer()->orderBy('default_display_order')->get() as $item) {
            CustomerMenuPermission::firstOrCreate(
                [
                    'id_rol' => $role->id_rol,
                    'route_name' => $item->route_name,
                ],
                [
                    'enabled' => $this->isCustomerMenuEnabledByDefault($role, $item->route_name),
                    'display_order' => $item->default_display_order,
                ]
            );
        }
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

    private function isCustomerMenuEnabledByDefault(Role $role, string $route): bool
    {
        // Roles de cliente (excepto ocasional) ven todo el menú cliente por defecto.
        return $role->nombre !== 'ocasional';
    }
}
