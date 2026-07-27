<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('global_configuration', function (Blueprint $table) {
            $table->decimal('min_price_package', 10, 2)->default(100)->after('step_purchase_input');
            $table->decimal('max_price_package', 10, 2)->default(5000)->after('min_price_package');
            $table->decimal('step_price_package', 10, 2)->default(100)->after('max_price_package');
        });

        DB::table('global_configuration')->update([
            'min_price_package' => 100,
            'max_price_package' => 5000,
            'step_price_package' => 100,
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::table('global_configuration', function (Blueprint $table) {
            $table->dropColumn([
                'min_price_package',
                'max_price_package',
                'step_price_package',
            ]);
        });
    }
};
