<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('provider_services', function (Blueprint $table) {
            $table->string('service_code', 32)->nullable()->unique()->after('key');
        });

        $placasProviderId = DB::table('providers')->where('adapter_code', 'placas')->value('id');
        $vindataProviderId = DB::table('providers')->where('adapter_code', 'vindata')->value('id');

        if ($placasProviderId) {
            DB::table('provider_services')
                ->where('provider_id', $placasProviderId)
                ->where('key', 'Placas_Service')
                ->update(['service_code' => 'placas_service']);
        }

        if ($vindataProviderId) {
            DB::table('provider_services')
                ->where('provider_id', $vindataProviderId)
                ->where('key', 'VHR')
                ->update(['service_code' => 'vhr']);

            DB::table('provider_services')
                ->where('provider_id', $vindataProviderId)
                ->where('key', 'NMVTISPlus')
                ->update(['service_code' => 'nmvtis_plus']);
        }

        Schema::table('provider_services', function (Blueprint $table) {
            $table->string('service_code', 32)->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('provider_services', function (Blueprint $table) {
            $table->dropUnique(['service_code']);
            $table->dropColumn('service_code');
        });
    }
};
