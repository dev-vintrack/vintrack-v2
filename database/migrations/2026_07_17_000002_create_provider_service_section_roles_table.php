<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provider_service_section_roles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('provider_service_section_id');
            $table->string('role', 32);
            $table->boolean('status')->default(true);
            $table->timestamps();

            $table->foreign('provider_service_section_id', 'pss_role_section_fk')
                ->references('id')
                ->on('provider_services_sections')
                ->onDelete('cascade');

            $table->unique(['provider_service_section_id', 'role'], 'pss_role_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_service_section_roles');
    }
};
