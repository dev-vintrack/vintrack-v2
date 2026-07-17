<?php

namespace App\Infrastructure\Persistence\Eloquent\Vehicles;

use App\Domain\Vehicles\Entities\Vehicle;
use App\Domain\Vehicles\Repositories\VehicleRepositoryInterface;
use App\Infrastructure\Persistence\Models\Vehicle as VehicleModel;
use DateTimeImmutable;

class VehicleRepository implements VehicleRepositoryInterface
{
    public function findByProviderAndValor(int $providerId, string $valor): ?Vehicle
    {
        $model = VehicleModel::where('provider_id', $providerId)
            ->where('valor', $valor)
            ->first();

        if (!$model) {
            return null;
        }

        return $this->toEntity($model);
    }

    public function save(Vehicle $vehicle): Vehicle
    {
        $model = VehicleModel::firstOrNew([
            'provider_id' => $vehicle->providerId(),
            'valor' => $vehicle->valor(),
        ]);

        $model->criterio = $vehicle->criterio();
        $model->marca = $vehicle->marca();
        $model->modelo = $vehicle->modelo();
        $model->anio = $vehicle->anio();
        $model->ultimo_status_robo = $vehicle->ultimoStatusRobo();
        $model->total_consultas = $vehicle->totalConsultas();
        $model->ultima_consulta_at = $vehicle->ultimaConsultaAt()->format('Y-m-d H:i:s');

        if (!$model->exists) {
            $model->created_at = $vehicle->createdAt()->format('Y-m-d H:i:s');
        }

        $model->save();

        return $this->toEntity($model);
    }

    public function all(): array
    {
        return VehicleModel::orderBy('ultima_consulta_at', 'desc')
            ->get()
            ->map(fn (VehicleModel $model) => $this->toEntity($model))
            ->all();
    }

    private function toEntity(VehicleModel $model): Vehicle
    {
        return new Vehicle(
            $model->id,
            (int) $model->provider_id,
            $model->criterio,
            $model->valor,
            $model->marca,
            $model->modelo,
            $model->anio,
            (bool) $model->ultimo_status_robo,
            (int) $model->total_consultas,
            new DateTimeImmutable($model->ultima_consulta_at),
            new DateTimeImmutable($model->created_at)
        );
    }
}
