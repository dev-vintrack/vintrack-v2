<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('global_configuration', function (Blueprint $table) {
            $table->id();
            $table->decimal('min_purchase_user', 10, 2)->default(10);
            $table->decimal('max_purchase_user', 10, 2)->default(1000);
            $table->decimal('step_purchase_input', 10, 2)->default(10);
            $table->unsignedInteger('min_validity_days')->default(30);
            $table->unsignedInteger('max_validity_days')->default(360);
            $table->unsignedInteger('step_validity_input')->default(30);
            $table->boolean('package_blocks_direct_credit')->default(true);
            $table->timestamps();
        });

        DB::table('global_configuration')->insert([
            'min_purchase_user'           => 10,
            'max_purchase_user'           => 1000,
            'step_purchase_input'         => 10,
            'min_validity_days'           => 30,
            'max_validity_days'           => 360,
            'step_validity_input'         => 30,
            'package_blocks_direct_credit' => true,
            'created_at'                  => now(),
            'updated_at'                  => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('global_configuration');
    }
};
