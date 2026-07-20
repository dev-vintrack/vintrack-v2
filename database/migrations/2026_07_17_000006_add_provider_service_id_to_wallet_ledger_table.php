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

        DB::statement(<<<'SQL'
            UPDATE wallet_ledger AS wl
            JOIN user_provider_wallets AS w ON w.id = wl.wallet_id
            SET wl.provider_service_id = w.provider_service_id
            WHERE w.provider_service_id IS NOT NULL
        SQL);
    }

    public function down(): void
    {
        Schema::table('wallet_ledger', function (Blueprint $table) {
            $table->dropForeign(['provider_service_id']);
            $table->dropColumn('provider_service_id');
        });
    }
};
