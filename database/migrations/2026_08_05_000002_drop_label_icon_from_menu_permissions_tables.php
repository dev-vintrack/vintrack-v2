<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admin_menu_permissions', function (Blueprint $table) {
            $table->dropColumn(['label', 'icon']);
        });

        Schema::table('customer_menu_permissions', function (Blueprint $table) {
            $table->dropColumn(['label', 'icon']);
        });
    }

    public function down(): void
    {
        Schema::table('admin_menu_permissions', function (Blueprint $table) {
            $table->string('label', 128)->after('route_name');
            $table->string('icon', 64)->nullable()->after('label');
        });

        Schema::table('customer_menu_permissions', function (Blueprint $table) {
            $table->string('label', 128)->after('route_name');
            $table->string('icon', 64)->nullable()->after('label');
        });
    }
};
