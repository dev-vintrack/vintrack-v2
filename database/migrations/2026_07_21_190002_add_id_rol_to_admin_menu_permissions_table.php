<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admin_menu_permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('id_rol')->nullable()->after('id');
            $table->foreign('id_rol')->references('id_rol')->on('roles')->onDelete('cascade');
        });

        DB::statement("
            UPDATE admin_menu_permissions
            SET id_rol = (
                SELECT id_rol FROM roles WHERE roles.nombre = admin_menu_permissions.role LIMIT 1
            )
        ");

        Schema::table('admin_menu_permissions', function (Blueprint $table) {
            $table->dropUnique(['role', 'route_name']);
            $table->dropIndex(['role', 'enabled', 'display_order']);
            $table->dropColumn('role');
            $table->unique(['id_rol', 'route_name']);
            $table->index(['id_rol', 'enabled', 'display_order']);
        });
    }

    public function down(): void
    {
        Schema::table('admin_menu_permissions', function (Blueprint $table) {
            $table->string('role', 32)->after('id');
        });

        DB::statement("
            UPDATE admin_menu_permissions
            SET role = (
                SELECT nombre FROM roles WHERE roles.id_rol = admin_menu_permissions.id_rol LIMIT 1
            )
        ");

        Schema::table('admin_menu_permissions', function (Blueprint $table) {
            $table->dropForeign(['id_rol']);
            $table->dropUnique(['id_rol', 'route_name']);
            $table->dropIndex(['id_rol', 'enabled', 'display_order']);
            $table->dropColumn('id_rol');
            $table->unique(['role', 'route_name']);
            $table->index(['role', 'enabled', 'display_order']);
        });
    }
};
