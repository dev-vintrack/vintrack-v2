<?php

use App\Models\Role;
use App\Models\RoleType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->foreignId('role_type_id')->nullable()->after('descripcion')->constrained('role_types');
            $table->string('home_route', 64)->nullable()->after('role_type_id');
            $table->boolean('requires_approval')->default(false)->after('home_route');
        });

        if (! Schema::hasTable('role_types')) {
            return;
        }

        // Poblar tipos de rol por defecto si no existen.
        $adminType = RoleType::firstOrCreate(
            ['type_name' => 'Administrativo'],
            [
                'description' => 'Roles con acceso al panel administrativo',
                'color' => 'danger',
                'is_admin' => true,
                'is_customer' => false,
                'status' => true,
                'display_order' => 1,
            ]
        );

        $customerType = RoleType::firstOrCreate(
            ['type_name' => 'Cliente'],
            [
                'description' => 'Roles de clientes finales',
                'color' => 'info',
                'is_admin' => false,
                'is_customer' => true,
                'status' => true,
                'display_order' => 2,
            ]
        );

        $homeRoutes = [
            'admin'              => 'home',
            'analista'           => 'home',
            'soporte'            => 'home',
            'cliente_registrado' => 'home.cliente',
            'perito'             => 'home.perito',
            'oficial'            => 'home.oficial',
            'ocasional'          => 'home.ocasional',
            'nuevo_rol'          => 'home',
        ];

        $requiresApproval = ['perito', 'oficial'];

        foreach (Role::all() as $role) {
            $isAdmin = in_array($role->nombre, ['admin', 'analista', 'soporte'], true);
            $role->update([
                'role_type_id' => $isAdmin ? $adminType->id : $customerType->id,
                'home_route' => $homeRoutes[$role->nombre] ?? null,
                'requires_approval' => in_array($role->nombre, $requiresApproval, true),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropForeign(['role_type_id']);
            $table->dropColumn(['role_type_id', 'home_route', 'requires_approval']);
        });
    }
};
