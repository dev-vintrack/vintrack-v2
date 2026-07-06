<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('nombre', 128)->nullable()->after('name');
            $table->string('telefono', 32)->nullable()->after('email');
            $table->string('rol', 32)->default('usuario')->after('telefono');
            $table->boolean('activo')->default(true)->after('rol');
            $table->timestamp('approved_at')->nullable()->after('activo');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['nombre', 'telefono', 'rol', 'activo', 'approved_at']);
        });
    }
};
