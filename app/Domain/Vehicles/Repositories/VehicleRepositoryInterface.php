<?php

namespace App\Domain\Vehicles\Repositories;

use App\Domain\Vehicles\Entities\Vehicle;
use DateTimeImmutable;

interface VehicleRepositoryInterface
{
    public function findByProviderServiceAndValor(int $providerServiceId, string $valor): ?Vehicle;

    public function save(Vehicle $vehicle): Vehicle;

    /**
     * @return array<int, Vehicle>
     */
    public function all(): array;
}
