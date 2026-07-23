<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('id_rol')->nullable()->after('telefono');
            $table->foreign('id_rol')->references('id_rol')->on('roles')->onDelete('set null');
        });

        DB::statement("
            UPDATE users
            SET id_rol = COALESCE(
                (SELECT id_rol FROM roles WHERE roles.nombre = users.rol LIMIT 1),
                (SELECT id_rol FROM roles WHERE roles.nombre = 'cliente_registrado' LIMIT 1)
            )
        ");

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('rol');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('rol', 32)->nullable()->after('telefono');
        });

        DB::statement("
            UPDATE users
            SET rol = COALESCE(
                (SELECT nombre FROM roles WHERE roles.id_rol = users.id_rol LIMIT 1),
                'cliente_registrado'
            )
        ");

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['id_rol']);
            $table->dropColumn('id_rol');
        });
    }
};
