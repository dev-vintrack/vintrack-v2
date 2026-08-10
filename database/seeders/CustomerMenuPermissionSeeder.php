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
     * @return list<string>
     */
    private function menuItems(): array
    {
        return [
            'customer.credits',
            'customer.movements',
            'customer.consultations',
            'customer.vin-decoder',
        ];
    }

    public function run(): void
    {
        $customerRoles = ['cliente_registrado', 'perito', 'oficial', 'unidad_analisis'];
        $items = $this->menuItems();

        foreach ($customerRoles as $roleName) {
            $role = Role::firstOrCreateByName($roleName);
            $order = 0;

            foreach ($items as $routeName) {
                CustomerMenuPermission::firstOrCreate(
                    [
                        'id_rol' => $role->id_rol,
                        'route_name' => $routeName,
                    ],
                    [
                        'enabled' => true,
                        'display_order' => $order++,
                    ]
                );
            }
        }
    }
}
