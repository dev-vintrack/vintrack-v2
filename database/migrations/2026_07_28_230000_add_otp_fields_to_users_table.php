<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('email_otp', 6)->nullable()->after('email_verified_at');
            $table->timestamp('email_otp_expire')->nullable()->after('email_otp');
            $table->boolean('es_oficial')->default(false)->after('status');
            $table->string('entidad', 128)->nullable()->after('es_oficial');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['email_otp', 'email_otp_expire', 'es_oficial', 'entidad']);
        });
    }
};
