<?php

namespace Database\Seeders;

use App\Infrastructure\Persistence\Models\Provider;
use App\Infrastructure\Persistence\Models\ProviderService;
use App\Infrastructure\Persistence\Models\ProviderServiceRole;
use App\Models\Role;
use Illuminate\Database\Seeder;

class ProviderSeeder extends Seeder
{
    public function run(): void
    {
        $placas = Provider::firstOrCreate(
            ['code' => 'PLACAS'],
            [
                'name' => 'Placas.info',
                'base_url' => 'https://placas.info/api/v2/consultar/',
                'policies_json' => [
                    'debitTiming' => 'postAccept',
                    'creditCost' => 1.0,
                    'resetPeriod' => 'none',
                    'carryOver' => true,
                    'expireAfterDays' => null,
                ],
                'enabled' => true,
            ]
        );

        $vindata = Provider::firstOrCreate(
            ['code' => 'VINDATA'],
            [
                'name' => 'VINData',
                'base_url' => 'https://api.vindata.com/v1',
                'policies_json' => [
                    'debitTiming' => 'postAccept',
                    'creditCost' => 1.0,
                    'resetPeriod' => 'none',
                    'carryOver' => true,
                    'expireAfterDays' => null,
                ],
                'enabled' => true,
            ]
        );

        $placasService = ProviderService::updateOrCreate(
            ['provider_id' => $placas->id, 'key' => 'Placas_Service'],
            [
                'name' => 'Placas Service',
                'credit_cost' => 0,
                'available_credits' => 0,
                'enabled' => true,
            ]
        );

        $this->ensureServiceRoles($placasService);

        $placasSections = [
            ['code' => 'repuve', 'name' => 'REPUVE'],
            ['code' => 'pgj', 'name' => 'PGJ'],
            ['code' => 'aviso', 'name' => 'Aviso Judicial'],
            ['code' => 'ocra', 'name' => 'OCRA'],
            ['code' => 'carfax', 'name' => 'CARFAX'],
            ['code' => 'rapi', 'name' => 'RAPI'],
        ];

        $roleNames = ['admin', 'analista', 'soporte', 'cliente_registrado', 'perito', 'oficial', 'ocasional'];
        $rapiInactiveFor = ['analista', 'soporte', 'cliente_registrado'];

        foreach ($placasSections as $section) {
            $sectionModel = \App\Infrastructure\Persistence\Models\ProviderServiceSection::updateOrCreate(
                ['provider_service_id' => $placasService->id, 'section_code' => $section['code']],
                [
                    'section_name' => $section['name'],
                    'status' => true,
                ]
            );

            foreach ($roleNames as $roleName) {
                $role = Role::firstOrCreateByName($roleName);
                \App\Infrastructure\Persistence\Models\ProviderServiceSectionRole::updateOrCreate(
                    ['provider_service_section_id' => $sectionModel->id, 'id_rol' => $role->id_rol],
                    [
                        'status' => ! ($section['code'] === 'rapi' && in_array($roleName, $rapiInactiveFor, true)),
                    ]
                );
            }
        }

        $vinDataServices = [
            ['key' => 'VHR', 'name' => 'VIN History Report', 'credit_cost' => 1],
            ['key' => 'NMVTISPlus', 'name' => 'NMVTIS+', 'credit_cost' => 1],
        ];

        foreach ($vinDataServices as $service) {
            $serviceModel = ProviderService::updateOrCreate(
                ['provider_id' => $vindata->id, 'key' => $service['key']],
                [
                    'name' => $service['name'],
                    'credit_cost' => $service['credit_cost'],
                    'available_credits' => 0,
                    'enabled' => true,
                ]
            );

            $this->ensureServiceRoles($serviceModel);
        }
    }

    private function ensureServiceRoles(ProviderService $service): void
    {
        foreach (Role::all() as $role) {
            ProviderServiceRole::firstOrCreate(
                [
                    'id_rol' => $role->id_rol,
                    'provider_service_id' => $service->id,
                ],
                ['status' => true]
            );
        }
    }
}
