<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consultations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('provider_id')->constrained('providers')->cascadeOnDelete();
            $table->string('criterio', 16)->comment('placa, niv, vin');
            $table->string('valor', 64);
            $table->string('api_id', 64)->nullable();
            $table->json('services')->nullable();
            $table->decimal('costo_credito', 10, 2)->default(0);
            $table->integer('http_status_post')->nullable();
            $table->integer('http_status_get')->nullable();
            $table->boolean('success')->default(false);
            $table->string('error_message', 255)->nullable();
            $table->boolean('alerta_robo')->default(false);
            $table->boolean('repuve_robo')->default(false);
            $table->boolean('pgj_robo')->default(false);
            $table->boolean('ocra_robo')->default(false);
            $table->boolean('carfax_robo')->default(false);
            $table->boolean('rapi_robo')->default(false);
            $table->json('response_json')->nullable();
            $table->integer('credits_api')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('valor');
            $table->index('criterio');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consultations');
    }
};
