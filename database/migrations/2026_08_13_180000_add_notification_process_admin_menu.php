<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const ROUTE = 'admin.notification-cases.index';

    public function up(): void
    {
        $now = now();
        DB::table('menu_items')->updateOrInsert(['route_name' => self::ROUTE], ['scope' => 'admin', 'label' => 'Proceso de Notificaciones', 'icon' => 'clipboard2-check', 'default_display_order' => 3, 'updated_at' => $now, 'created_at' => $now]);
        $roleIds = DB::table('roles')->whereIn('nombre', ['admin', 'analista'])->pluck('id_rol');
        foreach ($roleIds as $roleId) {
            DB::table('admin_menu_permissions')->updateOrInsert(['id_rol' => $roleId, 'route_name' => self::ROUTE], ['enabled' => true, 'display_order' => 3, 'updated_at' => $now, 'created_at' => $now]);
        }
    }

    public function down(): void
    {
        DB::table('admin_menu_permissions')->where('route_name', self::ROUTE)->delete();
        DB::table('menu_items')->where('route_name', self::ROUTE)->delete();
    }
};
