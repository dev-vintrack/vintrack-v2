<?php

use App\Infrastructure\Persistence\Models\ProviderService;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provider_service_roles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_rol');
            $table->unsignedBigInteger('provider_service_id');
            $table->boolean('status')->default(true);
            $table->timestamps();

            $table->unique(['id_rol', 'provider_service_id'], 'psr_role_service_unique');
            $table->foreign('id_rol', 'provider_service_roles_id_rol_foreign')
                ->references('id_rol')
                ->on('roles')
                ->cascadeOnDelete();
            $table->foreign('provider_service_id', 'provider_service_roles_provider_service_id_foreign')
                ->references('id')
                ->on('provider_services')
                ->cascadeOnDelete();
        });

        $rows = [];
        foreach (Role::all() as $role) {
            foreach (ProviderService::where('enabled', true)->get() as $service) {
                $rows[] = [
                    'id_rol' => $role->id_rol,
                    'provider_service_id' => $service->id,
                    'status' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        if (! empty($rows)) {
            DB::table('provider_service_roles')->insert($rows);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_service_roles');
    }
};
