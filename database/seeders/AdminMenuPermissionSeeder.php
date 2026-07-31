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
            ['route' => 'admin.vehicles.index',    'label' => 'Vehículos Registrados',  'icon' => 'car-front'],
            ['route' => 'admin.packages.active',     'label' => 'Paquetes Activos',       'icon' => 'box-seam'],
            ['route' => 'admin.credits.purchase',    'label' => 'Comprar Créditos',       'icon' => 'plus-circle'],
            ['route' => 'admin.packages.index',      'label' => 'Paquetes',               'icon' => 'boxes'],
            ['route' => 'admin.providers.index',     'label' => 'Proveedores',            'icon' => 'hdd-network'],
            ['route' => 'admin.users.index',         'label' => 'Usuarios',               'icon' => 'people'],
            ['route' => 'admin.menu-permissions.index', 'label' => 'Permisos de Menú',    'icon' => 'sliders'],
            ['route' => 'admin.customer-menu-permissions.index', 'label' => 'Permisos de Menú Cliente', 'icon' => 'sliders2'],
            ['route' => 'admin.notifications.index',    'label' => 'Notificaciones',       'icon' => 'envelope-check'],
            ['route' => 'admin.provider-service-roles.index', 'label' => 'Servicios por Rol', 'icon' => 'hand-thumbs-up'],
            ['route' => 'admin.roles.index',         'label' => 'Roles',                  'icon' => 'person-gear'],
            ['route' => 'admin.role-types.index',    'label' => 'Tipos de Rol',           'icon' => 'tags'],
        ];
    }

    public function run(): void
    {
        $items = $this->menuItems();

        foreach (array_keys(RoleHelper::ROLES) as $role) {
            $roleModel = Role::firstOrCreateByName($role);
            $order = 0;
            foreach ($items as $item) {
                $enabled = $this->isEnabledByDefault($role, $item['route']);

                AdminMenuPermission::firstOrCreate(
                    [
                        'id_rol' => $roleModel->id_rol,
                        'route_name' => $item['route'],
                    ],
                    [
                        'label' => $item['label'],
                        'icon' => $item['icon'],
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
