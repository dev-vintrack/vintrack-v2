<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_case_sequences', function (Blueprint $table) {
            $table->unsignedSmallInteger('year')->primary();
            $table->unsignedBigInteger('last_value')->default(0);
            $table->dateTime('updated_at', 6);
        });

        Schema::create('notification_case_user_guards', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->primary();
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->foreign('user_id')->references('id')->on('users')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::create('notification_case_vin_guards', function (Blueprint $table) {
            $table->char('vin_key', 17)->primary();
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
        });

        Schema::create('notification_case_source_guards', function (Blueprint $table) {
            $table->string('source_key', 128)->primary();
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
        });

        Schema::create('notification_case_consultation_reservations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('request_key', 128)->unique();
            $table->dateTime('expires_at', 6);
            $table->dateTime('consumed_at', 6)->nullable();
            $table->dateTime('released_at', 6)->nullable();
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->foreign('user_id')->references('id')->on('users')->restrictOnDelete()->restrictOnUpdate();
            $table->index(['user_id', 'expires_at', 'consumed_at', 'released_at'], 'notification_reservations_live_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_case_consultation_reservations');
        Schema::dropIfExists('notification_case_source_guards');
        Schema::dropIfExists('notification_case_vin_guards');
        Schema::dropIfExists('notification_case_user_guards');
        Schema::dropIfExists('notification_case_sequences');
    }
};
