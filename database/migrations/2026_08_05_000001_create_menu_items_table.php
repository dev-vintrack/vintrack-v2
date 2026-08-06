<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();
            $table->string('scope', 16); // admin | customer
            $table->string('route_name', 128)->unique();
            $table->string('label', 128);
            $table->string('icon', 64)->nullable();
            $table->unsignedSmallInteger('default_display_order')->default(0);
            $table->timestamps();

            $table->index(['scope']);
        });

        $now = now();

        $adminItems = [
            ['route' => 'home', 'label' => 'Inicio', 'icon' => 'house-door'],
            ['route' => 'admin.consultations.index', 'label' => 'Historial de Consultas', 'icon' => 'clipboard-data'],
            ['route' => 'admin.inventory.index', 'label' => 'Inventario Global', 'icon' => 'boxes'],
            ['route' => 'admin.purchases.index', 'label' => 'Compras', 'icon' => 'cart'],
            ['route' => 'admin.wallets.movements', 'label' => 'Movimientos Wallet', 'icon' => 'arrow-left-right'],
            ['route' => 'admin.wallets.index', 'label' => 'Créditos por Usuario', 'icon' => 'wallet'],
            ['route' => 'admin.vehicles.index', 'label' => 'Vehículos Registrados', 'icon' => 'car-front'],
            ['route' => 'admin.packages.active', 'label' => 'Paquetes Activos', 'icon' => 'box-seam'],
            ['route' => 'admin.credits.purchase', 'label' => 'Comprar Créditos', 'icon' => 'plus-circle'],
            ['route' => 'admin.packages.index', 'label' => 'Paquetes', 'icon' => 'boxes'],
            ['route' => 'admin.providers.index', 'label' => 'Proveedores', 'icon' => 'hdd-network'],
            ['route' => 'admin.users.index', 'label' => 'Usuarios', 'icon' => 'people'],
            ['route' => 'admin.menu-permissions.index', 'label' => 'Permisos de Menú', 'icon' => 'sliders'],
            ['route' => 'admin.customer-menu-permissions.index', 'label' => 'Permisos de Menú Cliente', 'icon' => 'sliders2'],
            ['route' => 'admin.provider-service-roles.index', 'label' => 'Servicios por Rol', 'icon' => 'hand-thumbs-up'],
            ['route' => 'admin.roles.index', 'label' => 'Roles', 'icon' => 'person-gear'],
            ['route' => 'admin.role-types.index', 'label' => 'Tipos de Rol', 'icon' => 'tags'],
            ['route' => 'admin.notifications.index', 'label' => 'Notificaciones', 'icon' => 'envelope-check'],
        ];

        $customerItems = [
            ['route' => 'customer.credits', 'label' => 'Mis créditos', 'icon' => 'wallet2'],
            ['route' => 'customer.movements', 'label' => 'Mis movimientos', 'icon' => 'arrow-left-right'],
            ['route' => 'customer.consultations', 'label' => 'Mis consultas', 'icon' => 'clock-history'],
            ['route' => 'customer.vin-decoder', 'label' => 'VIN Decoder', 'icon' => 'upc-scan'],
        ];

        $order = 0;
        foreach ($adminItems as $item) {
            DB::table('menu_items')->insertOrIgnore([
                'scope' => 'admin',
                'route_name' => $item['route'],
                'label' => $item['label'],
                'icon' => $item['icon'],
                'default_display_order' => $order++,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $order = 0;
        foreach ($customerItems as $item) {
            DB::table('menu_items')->insertOrIgnore([
                'scope' => 'customer',
                'route_name' => $item['route'],
                'label' => $item['label'],
                'icon' => $item['icon'],
                'default_display_order' => $order++,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // Defensivo: si existen route_name en las tablas de permisos que no están
        // en el catálogo anterior (por ejemplo, agregados manualmente en producción),
        // se incorporan usando la etiqueta del rol "admin"/primer rol de cliente encontrado.
        if (Schema::hasTable('admin_menu_permissions')) {
            $missingAdmin = DB::table('admin_menu_permissions')
                ->select('route_name', 'label', 'icon', 'display_order')
                ->whereNotIn('route_name', DB::table('menu_items')->pluck('route_name'))
                ->orderBy('display_order')
                ->get()
                ->unique('route_name');

            foreach ($missingAdmin as $item) {
                DB::table('menu_items')->insertOrIgnore([
                    'scope' => 'admin',
                    'route_name' => $item->route_name,
                    'label' => $item->label,
                    'icon' => $item->icon,
                    'default_display_order' => $item->display_order,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        if (Schema::hasTable('customer_menu_permissions')) {
            $missingCustomer = DB::table('customer_menu_permissions')
                ->select('route_name', 'label', 'icon', 'display_order')
                ->whereNotIn('route_name', DB::table('menu_items')->pluck('route_name'))
                ->orderBy('display_order')
                ->get()
                ->unique('route_name');

            foreach ($missingCustomer as $item) {
                DB::table('menu_items')->insertOrIgnore([
                    'scope' => 'customer',
                    'route_name' => $item->route_name,
                    'label' => $item->label,
                    'icon' => $item->icon,
                    'default_display_order' => $item->display_order,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_items');
    }
};
