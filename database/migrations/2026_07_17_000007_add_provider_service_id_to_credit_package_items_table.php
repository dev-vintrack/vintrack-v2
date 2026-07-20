<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('credit_package_items', function (Blueprint $table) {
            $table->foreignId('provider_service_id')->nullable()->after('provider_id')->constrained('provider_services')->nullOnDelete();
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
            DB::table('credit_package_items')
                ->where('provider_id', $placasProvider->id)
                ->update(['provider_service_id' => $placasService->id]);
        }

        if ($vinProvider && $nmvtisService) {
            DB::table('credit_package_items')
                ->where('provider_id', $vinProvider->id)
                ->update(['provider_service_id' => $nmvtisService->id]);
        }
    }

    public function down(): void
    {
        Schema::table('credit_package_items', function (Blueprint $table) {
            $table->dropForeign(['provider_service_id']);
            $table->dropColumn('provider_service_id');
        });
    }
};
