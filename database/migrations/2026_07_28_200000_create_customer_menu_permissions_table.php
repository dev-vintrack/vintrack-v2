<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_menu_permissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_rol');
            $table->string('route_name', 128);
            $table->string('label', 128);
            $table->string('icon', 64)->nullable();
            $table->boolean('enabled')->default(true);
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->timestamps();

            $table->foreign('id_rol')->references('id_rol')->on('roles')->onDelete('cascade');
            $table->unique(['id_rol', 'route_name']);
            $table->index(['id_rol', 'enabled', 'display_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_menu_permissions');
    }
};
