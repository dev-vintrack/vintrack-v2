<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('provider_id');
            $table->string('criterio', 32);
            $table->string('valor', 64);
            $table->string('marca', 128)->nullable();
            $table->string('modelo', 128)->nullable();
            $table->string('anio', 16)->nullable();
            $table->boolean('ultimo_status_robo')->default(false);
            $table->unsignedInteger('total_consultas')->default(0);
            $table->timestamp('ultima_consulta_at')->nullable();
            $table->timestamps();

            $table->unique(['provider_id', 'valor'], 'vehicles_provider_valor_unique');
            $table->index('ultima_consulta_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
