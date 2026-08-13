<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const ROUTE = 'customer.notification-cases.index';

    public function up(): void
    {
        $now = now();
        DB::table('menu_items')->updateOrInsert(['route_name' => self::ROUTE], [
            'scope' => 'customer',
            'label' => 'Proceso de Notificaciones',
            'icon' => 'file-earmark-text',
            'default_display_order' => 4,
            'updated_at' => $now,
            'created_at' => $now,
        ]);

        $roleIds = DB::table('roles')->join('role_types', 'role_types.id', '=', 'roles.role_type_id')
            ->where('role_types.is_customer', true)->whereIn('roles.nombre', ['cliente_registrado', 'perito', 'oficial', 'unidad_analisis'])
            ->pluck('roles.id_rol');
        foreach ($roleIds as $roleId) {
            DB::table('customer_menu_permissions')->updateOrInsert([
                'id_rol' => $roleId,
                'route_name' => self::ROUTE,
            ], [
                'enabled' => true,
                'display_order' => 4,
                'updated_at' => $now,
                'created_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('customer_menu_permissions')->where('route_name', self::ROUTE)->delete();
        DB::table('menu_items')->where('route_name', self::ROUTE)->delete();
    }
};
