<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        DB::table('menu_items')->where('route_name', 'customer.consultations')->update([
            'label' => 'Historial de Vehículos Consultados', 'icon' => 'clock-history', 'updated_at' => $now,
        ]);
        DB::table('menu_items')->where('route_name', 'admin.consultations.index')->update([
            'label' => 'Historial Global de Vehículos Consultados', 'icon' => 'clipboard-data', 'updated_at' => $now,
        ]);

        $roles = DB::table('roles')->whereIn('nombre', ['admin', 'analista', 'soporte'])->pluck('id_rol', 'nombre');
        foreach (['admin', 'analista'] as $role) {
            if (isset($roles[$role])) {
                DB::table('admin_menu_permissions')->updateOrInsert(
                    ['id_rol' => $roles[$role], 'route_name' => 'admin.consultations.index'],
                    ['enabled' => true, 'display_order' => 4, 'created_at' => $now, 'updated_at' => $now]
                );
            }
        }
        if (isset($roles['soporte'])) {
            DB::table('admin_menu_permissions')->where('id_rol', $roles['soporte'])
                ->where('route_name', 'admin.consultations.index')->update(['enabled' => false, 'updated_at' => $now]);
        }
    }

    public function down(): void
    {
        $now = now();
        DB::table('menu_items')->where('route_name', 'customer.consultations')->update(['label' => 'Mis consultas', 'updated_at' => $now]);
        DB::table('menu_items')->where('route_name', 'admin.consultations.index')->update(['label' => 'Historial de Consultas', 'updated_at' => $now]);
        $roles = DB::table('roles')->whereIn('nombre', ['analista', 'soporte'])->pluck('id_rol', 'nombre');
        if (isset($roles['analista'])) {
            DB::table('admin_menu_permissions')->where('id_rol', $roles['analista'])
                ->where('route_name', 'admin.consultations.index')->update(['enabled' => false, 'updated_at' => $now]);
        }
        if (isset($roles['soporte'])) {
            DB::table('admin_menu_permissions')->where('id_rol', $roles['soporte'])
                ->where('route_name', 'admin.consultations.index')->update(['enabled' => true, 'updated_at' => $now]);
        }
    }
};
