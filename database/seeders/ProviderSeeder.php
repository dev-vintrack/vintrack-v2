<?php

namespace Database\Seeders;

use App\Infrastructure\Persistence\Models\Provider;
use App\Infrastructure\Persistence\Models\ProviderService;
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

        $placasServices = [
            ['key' => 'repuve', 'name' => 'REPUVE', 'credit_cost' => 0],
            ['key' => 'pgj', 'name' => 'PGJ', 'credit_cost' => 0],
            ['key' => 'aviso', 'name' => 'Aviso Judicial', 'credit_cost' => 0],
            ['key' => 'ocra', 'name' => 'OCRA', 'credit_cost' => 0],
            ['key' => 'carfax', 'name' => 'CARFAX', 'credit_cost' => 0],
            ['key' => 'rapi', 'name' => 'RAPI', 'credit_cost' => 0],
        ];

        foreach ($placasServices as $service) {
            ProviderService::updateOrCreate(
                ['provider_id' => $placas->id, 'key' => $service['key']],
                [
                    'name' => $service['name'],
                    'credit_cost' => $service['credit_cost'],
                    'enabled' => true,
                ]
            );
        }

        $vinDataServices = [
            ['key' => 'VHR', 'name' => 'VIN History Report', 'credit_cost' => 1],
            ['key' => 'NMVTISPlus', 'name' => 'NMVTIS+', 'credit_cost' => 1],
        ];

        foreach ($vinDataServices as $service) {
            ProviderService::updateOrCreate(
                ['provider_id' => $vindata->id, 'key' => $service['key']],
                [
                    'name' => $service['name'],
                    'credit_cost' => $service['credit_cost'],
                    'enabled' => true,
                ]
            );
        }
    }
}
