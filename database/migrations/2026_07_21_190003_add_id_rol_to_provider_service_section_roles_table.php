<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('provider_service_section_roles', function (Blueprint $table) {
            $table->unsignedBigInteger('id_rol')->nullable()->after('provider_service_section_id');
            $table->foreign('id_rol')->references('id_rol')->on('roles')->onDelete('cascade');
        });

        DB::statement("
            UPDATE provider_service_section_roles
            SET id_rol = (
                SELECT id_rol FROM roles WHERE roles.nombre = provider_service_section_roles.role LIMIT 1
            )
        ");

        Schema::table('provider_service_section_roles', function (Blueprint $table) {
            $table->index('provider_service_section_id', 'pss_section_idx');
            $table->dropUnique('pss_role_unique');
            $table->dropColumn('role');
            $table->unique(['provider_service_section_id', 'id_rol'], 'pss_id_rol_unique');
        });
    }

    public function down(): void
    {
        Schema::table('provider_service_section_roles', function (Blueprint $table) {
            $table->string('role', 32)->after('provider_service_section_id');
        });

        DB::statement("
            UPDATE provider_service_section_roles
            SET role = (
                SELECT nombre FROM roles WHERE roles.id_rol = provider_service_section_roles.id_rol LIMIT 1
            )
        ");

        Schema::table('provider_service_section_roles', function (Blueprint $table) {
            $table->dropForeign(['id_rol']);
            $table->dropUnique('pss_id_rol_unique');
            $table->dropColumn('id_rol');
            $table->unique(['provider_service_section_id', 'role'], 'pss_role_unique');
        });
    }
};
