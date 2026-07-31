<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_service_id')->constrained('provider_services')->cascadeOnDelete();
            $table->string('event_type', 64);
            $table->boolean('enabled')->default(true);
            $table->decimal('low_balance_threshold', 10, 2)->nullable();
            $table->json('expiring_days')->nullable();
            $table->unsignedInteger('cooldown_hours')->default(72);
            $table->string('bcc_email')->nullable();
            $table->timestamps();

            $table->unique(['provider_service_id', 'event_type'], 'notification_policy_service_event_unique');
            $table->index(['event_type', 'enabled']);
        });

        Schema::create('notification_deliveries', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('provider_service_id')->nullable()->constrained('provider_services')->nullOnDelete();
            $table->string('event_type', 64);
            $table->string('related_type', 128)->nullable();
            $table->unsignedBigInteger('related_id')->nullable();
            $table->string('recipient')->nullable();
            $table->string('bcc')->nullable();
            $table->string('subject');
            $table->string('status', 24)->default('pending');
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->string('dedup_key', 191)->unique();
            $table->timestamp('scheduled_for')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('error')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['event_type', 'status']);
            $table->index(['provider_service_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });

        $events = [
            'wallet.low_balance',
            'wallet.zero_balance',
            'wallet.expiring',
            'wallet.expired',
            'consultation.risk_alert',
        ];

        foreach (DB::table('provider_services')->get(['id', 'min_alert_client']) as $service) {
            foreach ($events as $event) {
                DB::table('notification_policies')->insert([
                    'provider_service_id' => $service->id,
                    'event_type' => $event,
                    'enabled' => true,
                    'low_balance_threshold' => $event === 'wallet.low_balance' ? $service->min_alert_client : null,
                    'expiring_days' => $event === 'wallet.expiring' ? json_encode([7, 3, 1]) : null,
                    'cooldown_hours' => $event === 'wallet.low_balance' ? 72 : 0,
                    'bcc_email' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        $adminRoleId = DB::table('roles')->where('nombre', 'admin')->value('id_rol');
        if ($adminRoleId) {
            DB::table('admin_menu_permissions')->updateOrInsert(
                ['id_rol' => $adminRoleId, 'route_name' => 'admin.notifications.index'],
                [
                    'label' => 'Notificaciones',
                    'icon' => 'envelope-check',
                    'enabled' => true,
                    'display_order' => 16,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    public function down(): void
    {
        DB::table('admin_menu_permissions')->where('route_name', 'admin.notifications.index')->delete();
        Schema::dropIfExists('notification_deliveries');
        Schema::dropIfExists('notification_policies');
    }
};
