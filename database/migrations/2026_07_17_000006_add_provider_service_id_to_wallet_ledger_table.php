<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wallet_ledger', function (Blueprint $table) {
            $table->foreignId('provider_service_id')->nullable()->after('wallet_id')->constrained('provider_services')->nullOnDelete();
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement(<<<'SQL'
                UPDATE wallet_ledger AS wl
                JOIN user_provider_wallets AS w ON w.id = wl.wallet_id
                SET wl.provider_service_id = w.provider_service_id
                WHERE w.provider_service_id IS NOT NULL
            SQL);
        } else {
            DB::statement(<<<'SQL'
                UPDATE wallet_ledger
                SET provider_service_id = (
                    SELECT provider_service_id FROM user_provider_wallets
                    WHERE user_provider_wallets.id = wallet_ledger.wallet_id
                )
                WHERE wallet_id IN (SELECT id FROM user_provider_wallets WHERE provider_service_id IS NOT NULL)
            SQL);
        }
    }

    public function down(): void
    {
        Schema::table('wallet_ledger', function (Blueprint $table) {
            if (DB::getDriverName() === 'mysql') {
                $table->dropForeign(['provider_service_id']);
            }
            $table->dropColumn('provider_service_id');
        });
    }
};
