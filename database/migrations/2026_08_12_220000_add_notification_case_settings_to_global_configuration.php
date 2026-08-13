<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('global_configuration', function (Blueprint $table) {
            $table->unsignedSmallInteger('notification_case_deadline_days')->default(3);
            $table->unsignedSmallInteger('notification_case_max_open_days')->default(30);
            $table->unsignedSmallInteger('notification_case_reuse_days')->default(90);
            $table->unsignedSmallInteger('notification_case_max_pending')->default(3);
            $table->unsignedSmallInteger('notification_case_max_files')->default(8);
            $table->unsignedBigInteger('notification_case_max_file_bytes')->default(3145728);
            $table->string('notification_case_timezone', 64)->default('America/Mexico_City');
            // 150 seconds covers the largest configured provider timeout (60s) plus
            // the Placas polling window (40s), transport/setup time and retry margin.
            $table->unsignedSmallInteger('notification_case_reservation_ttl_seconds')->default(150);
        });
    }

    public function down(): void
    {
        Schema::table('global_configuration', function (Blueprint $table) {
            $table->dropColumn([
                'notification_case_deadline_days',
                'notification_case_max_open_days',
                'notification_case_reuse_days',
                'notification_case_max_pending',
                'notification_case_max_files',
                'notification_case_max_file_bytes',
                'notification_case_timezone',
                'notification_case_reservation_ttl_seconds',
            ]);
        });
    }
};
