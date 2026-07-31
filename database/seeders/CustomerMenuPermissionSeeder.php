<?php

namespace Database\Seeders;

use App\Infrastructure\Persistence\Models\CustomerMenuPermission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class CustomerMenuPermissionSeeder extends Seeder
{
    /**
     * Default customer portal menu options.
     *
     * @return array<int, array{route: string, label: string, icon: string|null}>
     */
    private function menuItems(): array
    {
        return [
            ['route' => 'customer.credits',       'label' => 'Mis créditos',   'icon' => 'wallet2'],
            ['route' => 'customer.movements',     'label' => 'Mis movimientos', 'icon' => 'arrow-left-right'],
            ['route' => 'customer.consultations', 'label' => 'Mis consultas',  'icon' => 'clock-history'],
        ];
    }

    public function run(): void
    {
        $customerRoles = ['cliente_registrado', 'perito', 'oficial'];
        $items = $this->menuItems();

        foreach ($customerRoles as $roleName) {
            $role = Role::firstOrCreateByName($roleName);
            $order = 0;

            foreach ($items as $item) {
                CustomerMenuPermission::firstOrCreate(
                    [
                        'id_rol' => $role->id_rol,
                        'route_name' => $item['route'],
                    ],
                    [
                        'label' => $item['label'],
                        'icon' => $item['icon'],
                        'enabled' => true,
                        'display_order' => $order++,
                    ]
                );
            }
        }
    }
}
