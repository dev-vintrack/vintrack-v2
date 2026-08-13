<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_case_documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('notification_case_id');
            $table->unsignedBigInteger('uploaded_by_user_id');
            $table->string('original_name', 255);
            $table->string('storage_disk', 32);
            $table->string('storage_key', 255);
            $table->string('mime_type', 64);
            $table->string('extension', 8);
            $table->unsignedBigInteger('size_bytes');
            $table->char('sha256', 64);
            $table->dateTime('removed_at', 6)->nullable();
            $table->unsignedBigInteger('removed_by_user_id')->nullable();
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->unique(['storage_disk', 'storage_key'], 'notification_case_documents_storage_uq');
            $table->index(['notification_case_id', 'removed_at', 'id'], 'notification_case_documents_active_idx');
            $table->foreign('notification_case_id')->references('id')->on('notification_cases')->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('uploaded_by_user_id')->references('id')->on('users')->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('removed_by_user_id')->references('id')->on('users')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::create('notification_case_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('notification_case_id')->nullable();
            $table->string('event_key', 191)->unique();
            $table->string('event_type', 64);
            $table->unsignedBigInteger('actor_user_id')->nullable();
            $table->string('actor_type', 32);
            $table->dateTime('occurred_at', 6);
            $table->string('from_status', 32)->nullable();
            $table->string('to_status', 32)->nullable();
            $table->text('reason')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->string('correlation_id', 128);
            $table->string('request_key', 128)->nullable();
            $table->string('parent_correlation_id', 128)->nullable();
            $table->string('field_name', 64)->nullable();
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->text('metadata')->nullable();
            $table->dateTime('created_at', 6);
            $table->index(['notification_case_id', 'occurred_at', 'id'], 'notification_case_events_timeline_idx');
            $table->index(['event_type', 'occurred_at', 'id'], 'notification_case_events_type_idx');
            $table->foreign('notification_case_id')->references('id')->on('notification_cases')->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('actor_user_id')->references('id')->on('users')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::create('notification_outbox', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('case_id')->nullable();
            $table->unsignedBigInteger('recipient_user_id');
            $table->string('event_type', 64);
            $table->string('channel', 16);
            $table->string('dedup_key', 191)->unique();
            $table->text('payload');
            $table->dateTime('available_at', 6);
            $table->string('status', 16)->default('PENDING');
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->dateTime('locked_at', 6)->nullable();
            $table->string('locked_by', 128)->nullable();
            $table->dateTime('sent_at', 6)->nullable();
            $table->dateTime('failed_at', 6)->nullable();
            $table->text('last_error')->nullable();
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->index(['status', 'available_at', 'id'], 'notification_outbox_delivery_idx');
            $table->foreign('case_id')->references('id')->on('notification_cases')->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('recipient_user_id')->references('id')->on('users')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::create('portal_notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('outbox_id')->unique();
            $table->unsignedBigInteger('recipient_user_id');
            $table->unsignedBigInteger('case_id')->nullable();
            $table->string('type', 64);
            $table->string('title', 191);
            $table->text('body');
            $table->string('action_path', 255)->nullable();
            $table->dateTime('read_at', 6)->nullable();
            $table->dateTime('created_at', 6);
            $table->index(['recipient_user_id', 'read_at', 'created_at', 'id'], 'portal_notifications_inbox_idx');
            $table->foreign('outbox_id')->references('id')->on('notification_outbox')->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('recipient_user_id')->references('id')->on('users')->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('case_id')->references('id')->on('notification_cases')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::create('notification_case_vin_reconciliations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('notification_case_id');
            $table->unsignedBigInteger('conflicting_case_id');
            $table->string('incident_key', 191)->unique();
            $table->string('status', 16)->default('OPEN');
            $table->dateTime('detected_at', 6);
            $table->dateTime('resolved_at', 6)->nullable();
            $table->unsignedBigInteger('resolved_by_user_id')->nullable();
            $table->text('reason')->nullable();
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->foreign('notification_case_id', 'nc_vin_recon_case_fk')->references('id')->on('notification_cases')->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('conflicting_case_id', 'nc_vin_recon_conflict_fk')->references('id')->on('notification_cases')->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('resolved_by_user_id', 'nc_vin_recon_resolver_fk')->references('id')->on('users')->restrictOnDelete()->restrictOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_case_vin_reconciliations');
        Schema::dropIfExists('portal_notifications');
        Schema::dropIfExists('notification_outbox');
        Schema::dropIfExists('notification_case_events');
        Schema::dropIfExists('notification_case_documents');
    }
};
