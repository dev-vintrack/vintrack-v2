<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            $fkExists = DB::select("SELECT 1 FROM information_schema.table_constraints WHERE constraint_schema = DATABASE() AND table_name = 'user_provider_wallets' AND constraint_name = 'user_provider_wallets_provider_id_foreign'");
            if (! empty($fkExists)) {
                DB::statement('ALTER TABLE user_provider_wallets DROP FOREIGN KEY user_provider_wallets_provider_id_foreign');
            }
        }

        Schema::table('user_provider_wallets', function (Blueprint $table) {
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
            DB::table('user_provider_wallets')
                ->where('provider_id', $placasProvider->id)
                ->update(['provider_service_id' => $placasService->id]);
        }

        if ($vinProvider && $nmvtisService) {
            DB::table('user_provider_wallets')
                ->where('provider_id', $vinProvider->id)
                ->update(['provider_service_id' => $nmvtisService->id]);
        }

        Schema::table('user_provider_wallets', function (Blueprint $table) {
            $table->index('user_id', 'upw_user_id_index');
            $table->dropUnique(['user_id', 'provider_id']);
            $table->unique(['user_id', 'provider_service_id'], 'upw_user_service_unique');

            if (DB::getDriverName() === 'mysql') {
                $table->unsignedBigInteger('provider_id')->nullable()->change();
            }
        });
    }

    public function down(): void
    {
        Schema::table('user_provider_wallets', function (Blueprint $table) {
            if (DB::getDriverName() === 'mysql') {
                $table->dropForeign(['provider_service_id']);
            }
            $table->dropColumn('provider_service_id');
            $table->unique(['user_id', 'provider_id'], 'upw_user_provider_unique');
        });
    }
};
