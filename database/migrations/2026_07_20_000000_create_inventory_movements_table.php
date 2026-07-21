<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_service_id')->constrained('provider_services')->cascadeOnDelete();
            $table->string('type', 32); // purchase, sale, expiry_return, adjustment
            $table->decimal('quantity', 10, 2);
            $table->string('reference_type', 64)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->foreignId('admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('provider_service_id');
            $table->index('type');
            $table->index(['reference_type', 'reference_id']);
        });

        DB::statement(<<<'SQL'
            INSERT INTO inventory_movements (provider_service_id, type, quantity, reference_type, reference_id, admin_id, notes, created_at, updated_at)
            SELECT id, 'adjustment', available_credits, NULL, NULL, NULL, 'Saldo inicial de inventario', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
            FROM provider_services
            WHERE available_credits > 0
        SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
    }
};
