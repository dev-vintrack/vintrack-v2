<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->bigIncrements('id_rol');
            $table->string('nombre', 32)->unique();
            $table->string('descripcion', 255)->nullable();
            $table->boolean('status')->default(true);
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->timestamp('fecha_creacion')->useCurrent();
            $table->timestamp('updated_at')->nullable();
        });

        $roles = [
            ['admin', 'Administrador del sistema', 1],
            ['analista', 'Analista operativo', 2],
            ['soporte', 'Soporte técnico', 3],
            ['cliente_registrado', 'Cliente registrado', 4],
            ['perito', 'Cliente perito', 5],
            ['oficial', 'Cliente con cargo oficial', 6],
            ['ocasional', 'Cliente ocasional', 7],
            ['nuevo_rol', 'Nuevo rol por definir', 8],
        ];

        foreach ($roles as [$nombre, $descripcion, $orden]) {
            DB::table('roles')->insert([
                'nombre' => $nombre,
                'descripcion' => $descripcion,
                'status' => true,
                'display_order' => $orden,
                'fecha_creacion' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
