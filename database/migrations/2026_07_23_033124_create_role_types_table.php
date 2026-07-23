<?php

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
        Schema::create('role_types', function (Blueprint $table) {
            $table->id();
            $table->string('type_name', 32)->unique();
            $table->string('description', 255)->nullable();
            $table->string('color', 32)->nullable();
            $table->boolean('is_admin')->default(false);
            $table->boolean('is_customer')->default(false);
            $table->boolean('status')->default(true);
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('role_types');
    }
};
