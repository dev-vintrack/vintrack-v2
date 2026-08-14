<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consultation_operations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('provider_service_id');
            $table->string('idempotency_key_hash', 64);
            $table->string('request_fingerprint', 64);
            $table->string('status', 32);
            $table->unsignedBigInteger('consultation_id')->nullable();
            $table->text('response_snapshot')->nullable();
            $table->string('failure_code', 64)->nullable();
            $table->dateTime('provider_started_at', 6)->nullable();
            $table->dateTime('completed_at', 6)->nullable();
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);

            $table->unique(['user_id', 'idempotency_key_hash'], 'consult_ops_user_key_uq');
            $table->index(['status', 'updated_at'], 'consult_ops_status_updated_idx');
            $table->foreign('user_id')->references('id')->on('users')->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('provider_service_id')->references('id')->on('provider_services')->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('consultation_id')->references('id')->on('consultations')->restrictOnDelete()->restrictOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consultation_operations');
    }
};
