<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_menu_permissions', function (Blueprint $table) {
            $table->id();
            $table->string('role', 32);
            $table->string('route_name', 128);
            $table->string('label', 128);
            $table->string('icon', 64)->nullable();
            $table->boolean('enabled')->default(true);
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->timestamps();

            $table->unique(['role', 'route_name']);
            $table->index(['role', 'enabled', 'display_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_menu_permissions');
    }
};
