<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $placasProvider = DB::table('providers')->where('code', 'PLACAS')->first();
        if (! $placasProvider) {
            return;
        }

        $placasServiceId = DB::table('provider_services')->insertGetId([
            'provider_id' => $placasProvider->id,
            'key' => 'Placas_Service',
            'name' => 'Placas Service',
            'credit_cost' => 0,
            'available_credits' => 0,
            'enabled' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $sectionNames = [
            'repuve' => 'REPUVE',
            'pgj' => 'PGJ',
            'aviso' => 'Aviso Judicial',
            'ocra' => 'OCRA',
            'carfax' => 'CARFAX',
            'rapi' => 'RAPI',
        ];

        $sectionIds = [];
        foreach ($sectionNames as $code => $name) {
            $sectionIds[$code] = DB::table('provider_services_sections')->insertGetId([
                'provider_service_id' => $placasServiceId,
                'section_code' => $code,
                'section_name' => $name,
                'status' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('provider_services')
            ->where('provider_id', $placasProvider->id)
            ->where('id', '!=', $placasServiceId)
            ->delete();

        $roles = ['admin', 'analista', 'soporte', 'cliente_registrado', 'perito', 'oficial', 'ocasional'];
        $rapiInactiveFor = ['analista', 'soporte', 'cliente_registrado'];

        foreach ($sectionIds as $code => $sectionId) {
            foreach ($roles as $role) {
                $status = ! ($code === 'rapi' && in_array($role, $rapiInactiveFor, true));

                DB::table('provider_service_section_roles')->insert([
                    'provider_service_section_id' => $sectionId,
                    'role' => $role,
                    'status' => $status,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        // No se puede revertir de forma segura sin perder datos.
    }
};
