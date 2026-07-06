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

        $services = [
            'repuve' => 'REPUVE',
            'pgj' => 'PGJ',
            'aviso' => 'Aviso Judicial',
            'ocra' => 'OCRA',
            'carfax' => 'CARFAX',
            'rapi' => 'RAPI',
        ];

        foreach ($services as $key => $name) {
            ProviderService::firstOrCreate(
                ['provider_id' => $placas->id, 'key' => $key],
                ['name' => $name, 'enabled' => true]
            );
        }
    }
}
