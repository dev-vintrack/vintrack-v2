<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_cases', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('consultation_id')->unique();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('previous_case_id')->nullable();
            $table->string('case_number', 20)->unique();
            $table->char('vin', 17)->nullable();
            $table->char('vin_key', 17)->nullable();
            $table->string('recovery_place', 191)->nullable();
            $table->string('country', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->string('municipality', 150)->nullable();
            $table->string('neighborhood', 150)->nullable();
            $table->string('postal_code', 16)->nullable();
            $table->string('street', 191)->nullable();
            $table->string('street_number', 32)->nullable();
            $table->dateTime('recovered_at', 6)->nullable();
            $table->string('license_plate', 20)->nullable();
            $table->string('make', 100)->nullable();
            $table->string('model', 100)->nullable();
            $table->unsignedSmallInteger('model_year')->nullable();
            $table->string('engine_number', 64)->nullable();
            $table->string('color', 64)->nullable();
            $table->string('origin', 100)->nullable();
            $table->string('authority', 191)->nullable();
            $table->string('iph', 100)->nullable();
            $table->string('nuc', 100)->nullable();
            $table->string('investigation_file', 150)->nullable();
            $table->string('safekeeping', 191)->nullable();
            $table->string('inventory', 150)->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 32)->default('PENDING');
            $table->dateTime('notification_deadline_at', 0);
            $table->dateTime('opened_at', 6);
            $table->dateTime('auto_close_at', 6);
            $table->dateTime('submitted_at', 6)->nullable();
            $table->dateTime('last_submitted_at', 6)->nullable();
            $table->dateTime('review_started_at', 6)->nullable();
            $table->dateTime('rejected_at', 6)->nullable();
            $table->dateTime('validated_at', 6)->nullable();
            $table->dateTime('closed_at', 6)->nullable();
            $table->unsignedBigInteger('lock_version')->default(0);
            $table->string('creation_key', 128)->unique();
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);

            $table->foreign('consultation_id')->references('id')->on('consultations')->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('user_id')->references('id')->on('users')->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('previous_case_id')->references('id')->on('notification_cases')->restrictOnDelete()->restrictOnUpdate();
            $table->index(['vin_key', 'status', 'validated_at', 'opened_at', 'id'], 'notification_cases_vin_applicable_idx');
            $table->index(['user_id', 'status', 'id'], 'notification_cases_user_pending_idx');
            $table->index(['status', 'auto_close_at', 'id'], 'notification_cases_auto_close_idx');
            $table->index(['notification_deadline_at', 'status', 'id'], 'notification_cases_deadline_idx');
            $table->index(['license_plate', 'id'], 'notification_cases_plate_idx');
            $table->index(['created_at', 'id'], 'notification_cases_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_cases');
    }
};
