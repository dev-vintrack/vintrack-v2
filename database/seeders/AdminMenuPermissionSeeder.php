<?php

namespace Database\Seeders;

use App\Infrastructure\Persistence\Models\AdminMenuPermission;
use App\Models\Role;
use App\Presentation\Support\RoleHelper;
use Illuminate\Database\Seeder;

class AdminMenuPermissionSeeder extends Seeder
{
    /**
     * Default menu options and suggested order.
     *
     * Nota: label/icon ya no se guardan en customer/admin_menu_permissions (se movieron
     * a la tabla menu_items, ver 2026_08_05_000001/000002). Se conservan aqui unicamente
     * como referencia/orden de las rutas por defecto.
     *
     * @return list<string>
     */
    private function menuItems(): array
    {
        return [
            'home',
            'admin.consultations.index',
            'admin.inventory.index',
            'admin.purchases.index',
            'admin.wallets.movements',
            'admin.wallets.index',
            'admin.vehicles.index',
            'admin.packages.active',
            'admin.credits.purchase',
            'admin.packages.index',
            'admin.providers.index',
            'admin.users.index',
            'admin.menu-permissions.index',
            'admin.customer-menu-permissions.index',
            'admin.notifications.index',
            'admin.provider-service-roles.index',
            'admin.roles.index',
            'admin.role-types.index',
        ];
    }

    public function run(): void
    {
        $routes = $this->menuItems();

        foreach (array_keys(RoleHelper::ROLES) as $role) {
            $roleModel = Role::firstOrCreateByName($role);
            $order = 0;
            foreach ($routes as $route) {
                $enabled = $this->isEnabledByDefault($role, $route);

                AdminMenuPermission::firstOrCreate(
                    [
                        'id_rol' => $roleModel->id_rol,
                        'route_name' => $route,
                    ],
                    [
                        'enabled' => $enabled,
                        'display_order' => $order++,
                    ]
                );
            }
        }
    }

    private function isEnabledByDefault(string $role, string $route): bool
    {
        // Admin sees everything by default.
        if ($role === 'admin') {
            return true;
        }

        // Analista sees the original operational sections plus customer menu management.
        if ($role === 'analista') {
            return in_array($route, [
                'admin.providers.index',
                'admin.packages.index',
                'admin.credits.purchase',
                'admin.customer-menu-permissions.index',
            ], true);
        }

        // Soporte sees everything except the permission config screen and the home.
        if ($role === 'soporte') {
            return ! in_array($route, [
                'admin.menu-permissions.index',
                'admin.notifications.index',
                'admin.provider-service-roles.index',
                'admin.roles.index',
                'home',
            ], true);
        }

        // Analista does not access vehicles.
        if ($role === 'analista') {
            return in_array($route, [
                'admin.providers.index',
                'admin.packages.index',
                'admin.credits.purchase',
            ], true);
        }

        // Customer roles do not access admin menu at all.
        return false;
    }
}
