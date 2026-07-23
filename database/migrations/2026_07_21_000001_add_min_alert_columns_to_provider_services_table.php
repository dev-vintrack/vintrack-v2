<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('provider_services', function (Blueprint $table) {
            $table->decimal('min_alert_client', 10, 2)->default(5)->after('available_credits');
            $table->decimal('min_alert_admin', 10, 2)->default(5)->after('min_alert_client');
        });
    }

    public function down(): void
    {
        Schema::table('provider_services', function (Blueprint $table) {
            $table->dropColumn(['min_alert_client', 'min_alert_admin']);
        });
    }
};
