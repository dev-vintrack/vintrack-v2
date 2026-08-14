<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consultations', function (Blueprint $table) {
            $table->string('normalized_value', 64)->nullable()->after('valor');
        });

        DB::statement("UPDATE consultations SET normalized_value = UPPER(REPLACE(REPLACE(REPLACE(REPLACE(TRIM(valor), '-', ''), ' ', ''), '.', ''), '/', '')) WHERE LOWER(criterio) = 'placa'");
        DB::statement('UPDATE consultations SET normalized_value = UPPER(TRIM(valor)) WHERE normalized_value IS NULL');

        Schema::table('consultations', function (Blueprint $table) {
            $table->index(
                ['provider_service_id', 'normalized_value', 'criterio', 'created_at', 'id'],
                'consultations_service_normalized_created_idx'
            );
            $table->index(
                ['normalized_value', 'created_at', 'id'],
                'consultations_normalized_created_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('consultations', function (Blueprint $table) {
            $table->dropIndex('consultations_service_normalized_created_idx');
            $table->dropIndex('consultations_normalized_created_idx');
            $table->dropColumn('normalized_value');
        });
    }
};
