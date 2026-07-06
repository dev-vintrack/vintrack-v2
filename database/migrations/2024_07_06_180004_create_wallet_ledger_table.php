<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallet_ledger', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained('user_provider_wallets')->cascadeOnDelete();
            $table->decimal('delta', 10, 2);
            $table->string('reason', 128);
            $table->json('meta')->nullable();
            $table->string('correlation_id', 128)->unique();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_ledger');
    }
};
