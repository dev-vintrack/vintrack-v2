<?php

use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $order = 3;

        foreach (['cliente_registrado', 'perito', 'oficial'] as $roleName) {
            $role = Role::firstOrCreateByName($roleName);

            // Insert directo via query builder (no Eloquent): en este punto del historial
            // de migraciones las columnas label/icon aun existen y son NOT NULL, pero el
            // modelo CustomerMenuPermission ya no las incluye en $fillable (se removieron
            // cuando se dropearon en una migracion posterior). Usar el modelo aqui
            // descartaria label/icon por mass-assignment y rompería el INSERT en una
            // migracion desde cero (fresh install / CI).
            DB::table('customer_menu_permissions')->updateOrInsert(
                [
                    'id_rol' => $role->id_rol,
                    'route_name' => 'customer.vin-decoder',
                ],
                [
                    'label' => 'VIN Decoder',
                    'icon' => 'upc-scan',
                    'enabled' => true,
                    'display_order' => $order,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }

    public function down(): void
    {
        DB::table('customer_menu_permissions')->where('route_name', 'customer.vin-decoder')->delete();
    }
};
