<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('user_packages', 'status')) {
            Schema::table('user_packages', function (Blueprint $table) {
                $table->string('status', 16)->default('active')->after('expires_at');
            });
        }

        if (! Schema::hasColumn('user_provider_wallets', 'status')) {
            Schema::table('user_provider_wallets', function (Blueprint $table) {
                $table->string('status', 16)->default('active')->after('validity_end');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('user_packages', 'status')) {
            Schema::table('user_packages', function (Blueprint $table) {
                $table->dropColumn('status');
            });
        }

        if (Schema::hasColumn('user_provider_wallets', 'status')) {
            Schema::table('user_provider_wallets', function (Blueprint $table) {
                $table->dropColumn('status');
            });
        }
    }
};
