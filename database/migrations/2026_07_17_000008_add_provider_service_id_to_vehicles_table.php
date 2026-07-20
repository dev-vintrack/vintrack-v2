<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->foreignId('provider_service_id')
                ->nullable()
                ->after('provider_id')
                ->constrained('provider_services')
                ->nullOnDelete();
        });

        $placasProvider = DB::table('providers')->where('code', 'PLACAS')->first();
        $placasService = $placasProvider
            ? DB::table('provider_services')->where('provider_id', $placasProvider->id)->where('key', 'Placas_Service')->first()
            : null;

        $vinProvider = DB::table('providers')->where('code', 'VINDATA')->first();
        $nmvtisService = $vinProvider
            ? DB::table('provider_services')->where('provider_id', $vinProvider->id)->where('key', 'NMVTISPlus')->first()
            : null;

        if ($placasProvider && $placasService) {
            DB::table('vehicles')
                ->where('provider_id', $placasProvider->id)
                ->update(['provider_service_id' => $placasService->id]);
        }

        if ($vinProvider && $nmvtisService) {
            DB::table('vehicles')
                ->where('provider_id', $vinProvider->id)
                ->update(['provider_service_id' => $nmvtisService->id]);
        }

        DB::statement(<<<'SQL'
            UPDATE vehicles v
            SET v.provider_service_id = (
                SELECT id FROM provider_services
                WHERE provider_services.provider_id = v.provider_id
                LIMIT 1
            )
            WHERE v.provider_service_id IS NULL
        SQL);

        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropUnique('vehicles_provider_valor_unique');
            $table->unique(['provider_service_id', 'valor'], 'vehicles_provider_service_valor_unique');
        });
    }

    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropForeign(['provider_service_id']);
            $table->dropUnique('vehicles_provider_service_valor_unique');
            $table->dropColumn('provider_service_id');
        });

        Schema::table('vehicles', function (Blueprint $table) {
            $table->unique(['provider_id', 'valor'], 'vehicles_provider_valor_unique');
        });
    }
};
