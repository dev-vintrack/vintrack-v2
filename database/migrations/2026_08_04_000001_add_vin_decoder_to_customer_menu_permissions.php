<?php

use App\Infrastructure\Persistence\Models\CustomerMenuPermission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $order = 3;

        foreach (['cliente_registrado', 'perito', 'oficial'] as $roleName) {
            $role = Role::firstOrCreateByName($roleName);

            CustomerMenuPermission::firstOrCreate(
                [
                    'id_rol' => $role->id_rol,
                    'route_name' => 'customer.vin-decoder',
                ],
                [
                    'label' => 'VIN Decoder',
                    'icon' => 'upc-scan',
                    'enabled' => true,
                    'display_order' => $order,
                ]
            );
        }
    }

    public function down(): void
    {
        CustomerMenuPermission::where('route_name', 'customer.vin-decoder')->delete();
    }
};
