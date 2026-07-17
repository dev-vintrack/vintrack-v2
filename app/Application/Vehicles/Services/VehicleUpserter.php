<?php

namespace App\Application\Vehicles\Services;

use App\Domain\Consultas\Entities\Consultation;
use App\Domain\Vehicles\Entities\Vehicle;
use App\Domain\Vehicles\Repositories\VehicleRepositoryInterface;
use DateTimeImmutable;

class VehicleUpserter
{
    public function __construct(
        private readonly VehicleRepositoryInterface $vehicleRepository
    ) {
    }

    public function upsertFromConsultation(Consultation $consultation, string $providerCode): void
    {
        if (!$consultation->success()) {
            return;
        }

        $extracted = VehicleDataExtractor::extract($consultation->responseJson(), $providerCode);

        $vehicle = $this->vehicleRepository->findByProviderAndValor(
            $consultation->providerId(),
            $consultation->valor()
        );

        if ($vehicle === null) {
            $vehicle = new Vehicle(
                null,
                $consultation->providerId(),
                $consultation->criterio(),
                $consultation->valor(),
                $extracted['marca'],
                $extracted['modelo'],
                $extracted['anio'],
                $consultation->alertaRobo(),
                1,
                $consultation->createdAt(),
                new DateTimeImmutable()
            );
        } else {
            $vehicle->updateFromConsultation(
                $consultation->alertaRobo(),
                $consultation->createdAt(),
                $extracted['marca'],
                $extracted['modelo'],
                $extracted['anio']
            );
        }

        $this->vehicleRepository->save($vehicle);
    }
}
