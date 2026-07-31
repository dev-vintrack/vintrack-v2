<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('providers', function (Blueprint $table) {
            $table->string('adapter_code', 32)->nullable()->unique()->after('code');
        });

        DB::table('providers')
            ->where('code', 'PLACAS')
            ->update(['adapter_code' => 'placas']);

        DB::table('providers')
            ->where('code', 'VINDATA')
            ->update(['adapter_code' => 'vindata']);

        Schema::table('providers', function (Blueprint $table) {
            $table->string('adapter_code', 32)->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('providers', function (Blueprint $table) {
            $table->dropUnique(['adapter_code']);
            $table->dropColumn('adapter_code');
        });
    }
};
