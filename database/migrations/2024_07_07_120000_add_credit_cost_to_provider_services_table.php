<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('provider_services', function (Blueprint $table) {
            $table->decimal('credit_cost', 10, 2)->default(0)->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('provider_services', function (Blueprint $table) {
            $table->dropColumn('credit_cost');
        });
    }
};
