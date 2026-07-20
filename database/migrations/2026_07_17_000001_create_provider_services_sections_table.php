<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provider_services_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_service_id')->constrained('provider_services')->cascadeOnDelete();
            $table->string('section_code', 32);
            $table->string('section_name', 128);
            $table->boolean('status')->default(true);
            $table->timestamps();

            $table->unique(['provider_service_id', 'section_code'], 'pss_provider_service_section_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_services_sections');
    }
};
