<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provider_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_id')->constrained('providers')->cascadeOnDelete();
            $table->string('key', 32);
            $table->string('name', 128)->nullable();
            $table->boolean('enabled')->default(true);
            $table->timestamps();

            $table->unique(['provider_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_services');
    }
};
